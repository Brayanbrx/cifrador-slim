/* Mostrar / ocultar campos dinámicos según el algoritmo escogido */

document.addEventListener("DOMContentLoaded", () => {
  const tipo   = document.getElementById("tipoSelect");
  const show   = (id, cond) => {
    document.getElementById(id).style.display = cond ? "block" : "none";
  };

  const toggle = () => {
    // el campo Acción (cifrar/descifrar) no se usa en Kasiski
    show("campoAccion", tipo.value !== "kasiski");

    const necesitaClave = [
      "desplazamiento-clave",
      "monoalfabetica",
      "polialfabetica",
      "playfair"
    ].includes(tipo.value);

    show("campoClave",     necesitaClave);
    show("campoNumClave",  tipo.value === "periodicos");
    show("campoMatriz",    tipo.value === "hill");
    show("campoTamGrupo",  tipo.value === "grupos");
    show("campoOrden",     ["series", "columnas"].includes(tipo.value));
    show("campoOrdenCol",  tipo.value === "anagramacion");
    show("campoOrdenFil",  tipo.value === "anagramacion");
    show("campoFilas",     tipo.value === "filas");
    show("campoRieles",    tipo.value === "zigzag");
  };

  toggle();
  tipo.addEventListener("change", toggle);
});
