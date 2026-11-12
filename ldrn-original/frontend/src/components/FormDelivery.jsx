import { useState } from "react";

export function FormGuard() {
  const [loadedAt] = useState(() => Date.now());
  return <>
    <div aria-hidden="true" style={{position:"absolute",left:"-9999px",width:1,height:1,overflow:"hidden"}}>
      <label>Leave this field empty<input name="website" tabIndex={-1} autoComplete="off" /></label>
    </div>
    <input type="hidden" name="formLoadedAt" value={loadedAt} />
    <p className="text-sm leading-relaxed text-ink/70">Submitting saves your details privately in WordPress and sends a notification to the website manager so we can respond. CVs are accessible only to authorised staff. <a className="underline" href="/privacy">Privacy information</a></p>
  </>;
}

export function SubmissionReceipt({ receipt }) {
  if (!receipt?.reference) return null;
  return <p className="mt-4 text-sm leading-relaxed text-ink/80">Your reference: <strong>{receipt.reference}</strong><br />We will reply to <strong>{receipt.email}</strong>. Please keep this reference if you contact us.</p>;
}
