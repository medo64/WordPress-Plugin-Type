function medo64type_copy(id) {
    var codeElement = document.getElementById(id);
    navigator.clipboard.writeText(codeElement.innerText + '\n');
}
