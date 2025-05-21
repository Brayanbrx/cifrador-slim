document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("#cipherForm");
  const out  = document.querySelector("#resultado");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    out.textContent = "Procesando…";

    try {
      const r = await fetch("/cifrar", {method:"POST", body:new FormData(form)});
      out.textContent = await r.text();
    } catch { out.textContent = "Error de conexión"; }
  });
});

