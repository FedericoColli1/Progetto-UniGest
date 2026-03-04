// Restituisce il token JWT formattato per l'header Authorization
function getToken() {
    temp = localStorage.getItem('token');
    token = "Bearer " + temp;
    return token;
}

// Al caricamento del documento, controlla se l'utente è autenticato e gestisce i permessi
$(document).ready(function () { // Controllo se è stato effettuato l'accesso

    const token = localStorage.getItem("token");
    const exp = localStorage.getItem("exp");
    const role = localStorage.getItem("role");
    const permission = localStorage.getItem("permission");


    if (window.location.href != "http://localhost/user/nuovaPratica.html") {
        loginVerify(token);
        getPratiche(token);
    }
    else checkIfCanCreatePratica(role, permission);
    checkTokenValidity(exp);

});


// Crea un timer con il tempo di durata del token.
// Se scade o è già scaduto, forza il logout.
function checkTokenValidity(exp) {
    const expiringMoment = exp * 1000;
    const currentTime = Date.now();   // data in ms
    const expiringTime = expiringMoment - currentTime;

    if (expiringTime > 0) {
        setTimeout(() => forceLogout("Token Scaduto. Si prega di rieffettuare l'accesso"), expiringTime);
    } else {
        forceLogout("Token Scaduto. Si prega di rieffettuare l'accesso");
    }

}

// Esegue il logout forzato(cancella il localStorage e reindirizza alla home)
function forceLogout(messaggio) {
    localStorage.clear();
    alert(messaggio);
    window.location.href = 'http://localhost';
}

// Controlla se l'utente può accedere alla pagina di creazione pratica (solo user o jolly)
// Se non può viene obbligato ad uscire e rieffettuare il login.
function checkIfCanCreatePratica(role, permission) {   
    if (role != "base" && permission != 1)
        forceLogout("Non sei autorizzato ad accedere a questa pagina! Stiamo effettuando un logout forzato.");
}