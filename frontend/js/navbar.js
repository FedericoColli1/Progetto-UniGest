// Selezione degli element necessari
let sidebar = document.querySelector(".sidebar");
let closeBtn = document.querySelector("#btn");
let searchBtn = document.querySelector(".bx-search");
let body = document.querySelector("body")

// Apertura della sidebar e cambio dell'icona
closeBtn.addEventListener("click", () => {
    sidebar.classList.toggle("open");
    body.classList.toggle("open");
    menuBtnChange();
})



// Cambio icona di apertura e chiusura menù
function menuBtnChange() {
    if (sidebar.classList.contains("open")) {
        closeBtn.classList.replace("bx-menu", "bx-menu-alt-right");
        
    } else {
        closeBtn.classList.replace("bx-menu-alt-right", "bx-menu");
    }
}
menuBtnChange();

// Quando si preme sull'icona di logout viene fatto il logout
document.getElementById('log_out').addEventListener('click', function () {
    forceLogout('Logout effettuato con successo!');
});