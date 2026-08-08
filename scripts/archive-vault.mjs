#!/usr/bin/env node

import {
  createCipheriv,
  createDecipheriv,
  createHash,
  randomBytes,
  scrypt as scryptCallback,
} from "node:crypto";
import {
  createReadStream,
  createWriteStream,
  promises as fs,
} from "node:fs";
import path from "node:path";
import { Transform } from "node:stream";
import { pipeline } from "node:stream/promises";
import { promisify } from "node:util";

const scrypt = promisify(scryptCallback);
const MAGIC = Buffer.from("AMVAULT1", "ascii");
const TAG_BYTES = 16;
const SCRYPT = Object.freeze({ N: 131072, r: 8, p: 1 });

function usage() {
  console.error(
    "Usage: node archive-vault.mjs <encrypt|decrypt> <input> <output> " +
      "--passphrase-file <path>",
  );
  process.exit(2);
}

function parseArgs(argv) {
  const [mode, input, output, flag, passphraseFile] = argv;
  if (
    !["encrypt", "decrypt"].includes(mode) ||
    !input ||
    !output ||
    flag !== "--passphrase-file" ||
    !passphraseFile
  ) {
    usage();
  }
  return {
    mode,
    input: path.resolve(input),
    output: path.resolve(output),
    passphraseFile: path.resolve(passphraseFile),
  };
}

async function readPassphrase(filename) {
  const value = (await fs.readFile(filename, "utf8")).trim();
  if (value.length < 32) {
    throw new Error("The archive passphrase must be at least 32 characters.");
  }
  return value;
}

async function deriveKey(passphrase, salt, params) {
  return scrypt(passphrase, salt, 32, {
    N: params.N,
    r: params.r,
    p: params.p,
    maxmem: 256 * 1024 * 1024,
  });
}

function hashTap(hash) {
  return new Transform({
    transform(chunk, encoding, callback) {
      hash.update(chunk);
      callback(null, chunk);
    },
  });
}

async function encrypt(input, output, passphrase) {
  const inputStat = await fs.stat(input);
  if (!inputStat.isFile()) throw new Error("Encryption input is not a file.");

  const salt = randomBytes(32);
  const iv = randomBytes(12);
  const header = {
    version: 1,
    cipher: "aes-256-gcm",
    kdf: "scrypt",
    scrypt: SCRYPT,
    salt: salt.toString("base64"),
    iv: iv.toString("base64"),
    plainSize: inputStat.size,
    sourceName: path.basename(input),
  };
  const headerBytes = Buffer.from(JSON.stringify(header), "utf8");
  const headerLength = Buffer.alloc(4);
  headerLength.writeUInt32BE(headerBytes.length);
  const prefix = Buffer.concat([MAGIC, headerLength, headerBytes]);

  const key = await deriveKey(passphrase, salt, SCRYPT);
  const cipher = createCipheriv("aes-256-gcm", key, iv);
  cipher.setAAD(headerBytes);
  const plainHash = createHash("sha256");
  const encryptedHash = createHash("sha256");
  encryptedHash.update(prefix);

  const temp = `${output}.part-${process.pid}`;
  await fs.mkdir(path.dirname(output), { recursive: true });
  const destination = createWriteStream(temp, { flags: "wx", mode: 0o600 });
  destination.write(prefix);

  try {
    await pipeline(
      createReadStream(input),
      hashTap(plainHash),
      cipher,
      hashTap(encryptedHash),
      destination,
    );
    const tag = cipher.getAuthTag();
    encryptedHash.update(tag);
    await fs.appendFile(temp, tag);
    await fs.rename(temp, output);
  } catch (error) {
    await fs.rm(temp, { force: true });
    throw error;
  }

  const outputStat = await fs.stat(output);
  return {
    mode: "encrypt",
    input,
    output,
    plainSize: inputStat.size,
    encryptedSize: outputStat.size,
    plainSha256: plainHash.digest("hex"),
    encryptedSha256: encryptedHash.digest("hex"),
  };
}

async function decrypt(input, output, passphrase) {
  const handle = await fs.open(input, "r");
  let inputStat;
  let headerBytes;
  let header;
  let cipherStart;
  let tag;

  try {
    inputStat = await handle.stat();
    const prefix = Buffer.alloc(MAGIC.length + 4);
    await handle.read(prefix, 0, prefix.length, 0);
    if (!prefix.subarray(0, MAGIC.length).equals(MAGIC)) {
      throw new Error("Archive magic is invalid.");
    }

    const headerSize = prefix.readUInt32BE(MAGIC.length);
    if (headerSize < 2 || headerSize > 64 * 1024) {
      throw new Error("Archive header length is invalid.");
    }
    headerBytes = Buffer.alloc(headerSize);
    await handle.read(headerBytes, 0, headerSize, prefix.length);
    header = JSON.parse(headerBytes.toString("utf8"));
    cipherStart = prefix.length + headerSize;

    if (
      header.version !== 1 ||
      header.cipher !== "aes-256-gcm" ||
      header.kdf !== "scrypt"
    ) {
      throw new Error("Archive format is unsupported.");
    }
    if (inputStat.size <= cipherStart + TAG_BYTES) {
      throw new Error("Archive payload is truncated.");
    }

    tag = Buffer.alloc(TAG_BYTES);
    await handle.read(tag, 0, TAG_BYTES, inputStat.size - TAG_BYTES);
  } finally {
    await handle.close();
  }

  const salt = Buffer.from(header.salt, "base64");
  const iv = Buffer.from(header.iv, "base64");
  const key = await deriveKey(passphrase, salt, header.scrypt);
  const decipher = createDecipheriv("aes-256-gcm", key, iv);
  decipher.setAAD(headerBytes);
  decipher.setAuthTag(tag);
  const plainHash = createHash("sha256");

  const temp = `${output}.part-${process.pid}`;
  await fs.mkdir(path.dirname(output), { recursive: true });
  try {
    await pipeline(
      createReadStream(input, {
        start: cipherStart,
        end: inputStat.size - TAG_BYTES - 1,
      }),
      decipher,
      hashTap(plainHash),
      createWriteStream(temp, { flags: "wx", mode: 0o600 }),
    );
    const outputStat = await fs.stat(temp);
    if (outputStat.size !== Number(header.plainSize)) {
      throw new Error("Decrypted size does not match the authenticated header.");
    }
    await fs.rename(temp, output);
  } catch (error) {
    await fs.rm(temp, { force: true });
    throw error;
  }

  return {
    mode: "decrypt",
    input,
    output,
    plainSize: Number(header.plainSize),
    plainSha256: plainHash.digest("hex"),
  };
}

const args = parseArgs(process.argv.slice(2));
const passphrase = await readPassphrase(args.passphraseFile);
const result =
  args.mode === "encrypt"
    ? await encrypt(args.input, args.output, passphrase)
    : await decrypt(args.input, args.output, passphrase);
console.log(JSON.stringify(result));
