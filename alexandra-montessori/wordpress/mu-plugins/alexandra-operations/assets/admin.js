(function () {
  "use strict";

  document.addEventListener("change", function (event) {
    if (!event.target.matches(".am-ops-select-all")) return;
    const form = event.target.closest("form");
    if (!form) return;
    form
      .querySelectorAll('input[name="submission_ids[]"]')
      .forEach((checkbox) => {
        checkbox.checked = event.target.checked;
      });
  });

  document.addEventListener("submit", function (event) {
    const form = event.target;
    if (!form.matches(".am-ops-bulk-form")) return;
    const action = form.querySelector('[name="bulk_action"]')?.value;
    const selected = form.querySelectorAll(
      'input[name="submission_ids[]"]:checked',
    ).length;
    if (!action || selected === 0) {
      event.preventDefault();
      window.alert("Choose at least one submission and a bulk action.");
      return;
    }
    if (
      (action === "archive" || action === "quarantine") &&
      !window.confirm(`Apply this action to ${selected} selected record(s)?`)
    ) {
      event.preventDefault();
    }
  });
})();
