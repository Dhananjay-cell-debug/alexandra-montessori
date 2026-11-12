/* Content Forms - photo/file pickers, gallery, repeatable rows, counters. */
jQuery(function ($) {
  "use strict";

  var sizeUrl = function (a) {
    var s = a.sizes && (a.sizes.medium || a.sizes.medium_large || a.sizes.large || a.sizes.full);
    return (s || a).url;
  };

  /* Single photo or document. Value: attachment id, or "url:..." for a file
     that lives outside the Media Library (kept untouched until replaced). */
  $("[data-cf-media]").each(function () {
    var box = $(this);
    var kind = box.data("cf-media");
    var input = box.find("input[type=hidden]");
    var preview = box.find(".cf-image__preview");
    var file = box.find(".cf-file");
    var pick = box.find("[data-cf-pick]");
    var remove = box.find("[data-cf-remove]");
    var noun = kind === "image" ? "photo" : "file";
    var frame;

    pick.on("click", function () {
      if (!frame) {
        frame = wp.media({
          title: kind === "image" ? "Choose a photo" : "Choose a file",
          button: { text: "Use this " + noun },
          library: { type: kind },
          multiple: false
        });
        frame.on("select", function () {
          var a = frame.state().get("selection").first().toJSON();
          input.val(a.id);
          if (kind === "image") preview.prop("hidden", false).find("img").attr("src", sizeUrl(a));
          else file.prop("hidden", false).find("a").attr("href", a.url).text(a.filename || a.title);
          pick.text("Change " + noun);
          remove.prop("hidden", false);
        });
      }
      frame.open();
    });

    remove.on("click", function () {
      input.val("");
      preview.prop("hidden", true);
      file.prop("hidden", true);
      pick.text("Upload " + noun);
      remove.prop("hidden", true);
    });
  });

  /* Gallery: ordered list of {id, url}, stored as JSON in one hidden field. */
  $("[data-cf-gallery]").each(function () {
    var box = $(this);
    var input = box.find("input[type=hidden]");
    var list = box.find(".cf-gallery__list");
    var items = [];
    try { items = JSON.parse(input.val() || "[]"); } catch (e) { items = []; }
    var frame;

    var draw = function () {
      input.val(JSON.stringify(items));
      list.empty();
      items.forEach(function (item, i) {
        var li = $('<li class="cf-gallery__item"><img alt=""><button type="button" aria-label="Remove photo">&times;</button></li>');
        li.find("img").attr("src", item.thumb || item.url);
        li.find("button").on("click", function () { items.splice(i, 1); draw(); });
        list.append(li);
      });
    };

    box.find("[data-cf-gallery-add]").on("click", function () {
      if (!frame) {
        frame = wp.media({ title: "Add photos", button: { text: "Add to gallery" }, library: { type: "image" }, multiple: "add" });
        frame.on("select", function () {
          frame.state().get("selection").each(function (m) {
            var a = m.toJSON();
            if (!items.some(function (it) { return it.id === a.id; })) items.push({ id: a.id, url: a.url, thumb: sizeUrl(a) });
          });
          draw();
        });
      }
      frame.open();
    });
    draw();
  });

  /* Repeatable rows (e.g. social links). */
  $("[data-cf-repeater]").each(function () {
    var box = $(this);
    var name = box.data("name");
    var rows = box.find(".cf-repeater__rows");
    var tpl = box.find("template").html();
    var renumber = function () {
      rows.children().each(function (i) {
        $(this).find("[data-col]").each(function () {
          $(this).attr("name", name + "[" + i + "][" + $(this).data("col") + "]");
        });
      });
    };
    box.on("click", "[data-cf-row-remove]", function () { $(this).closest(".cf-repeater__row").remove(); renumber(); });
    box.find("[data-cf-row-add]").on("click", function () { rows.append(tpl); renumber(); rows.children().last().find("input").first().trigger("focus"); });
    renumber();
  });

  $("[data-cf-limit]").each(function () {
    var field = $(this);
    var limit = Number(field.data("cf-limit"));
    var count = $('[data-cf-count-for="' + field.attr("id") + '"]');
    var update = function () {
      var n = field.val().length;
      count.text(n + " / " + limit).toggleClass("is-over", n > limit);
    };
    field.on("input", update);
    update();
  });
});
