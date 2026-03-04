$(document).ready(function () {
    localStorage.clear();
})

$("form").submit(function (event) {
    event.preventDefault(); // Evita il ricaricamento della pagina
})

$("#login").click(function () {

    var url = "http://localhost:8080/users/login";
    //event.preventDefault();
    let emailValue = $("#email").val();
    let passwordValue = $("#password").val();

    console.log(passwordValue)

    if (isValidEmail(emailValue) && passwordValue != "") {
        $.ajax({
            url: url,
            type: "POST",
            data: JSON.stringify({ email: emailValue, password: passwordValue }),
            dataType: "json",
            contentType: "application/json",

            success: function (res) {
                if (res.Token) {
                    // Salva il token JWT nel localStorage
                    localStorage.setItem("token", res.Token);
                    initializeStorageAndRedirect(res.Token);

                } else {
                    alert("Login fallito: " + res.message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (jqXHR.status == 404) {
                    alert("Error " + jqXHR.status + ": email o password errata.");
                } else {
                    alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
                }

            }
        })
    }
})

function isValidEmail(str) {
    const regex = /^[^@]+@[^\s@\.]+(\.[^\s@\.]+)+$/; // se contiene un carattere non @, poi @, poi almeno un carattere non @, poi ., poi altri caratteri non chiocciole
    return regex.test(str);
}

function initializeStorageAndRedirect(token) {
    var payload = decodeToken(token);
    localStorage.setItem("exp", payload.exp);
    localStorage.setItem("iat", payload.iat);
    localStorage.setItem("nomeUtente", payload.iss);
    localStorage.setItem("role", payload.role);
    localStorage.setItem("permission", payload.permission);

    if (payload.permission === 1) {// jolly
        window.location.href = "jolly.html";
    }
    else if (payload.permission === 0 && payload.role === "base") // professore/ricercatore
        window.location.href = "user.html";
    else if (payload.permission === 0 && payload.role === "amministrativo") // professore/ricercatore
        window.location.href = "admin.html";
    else if (payload.permission === 0 && payload.role === "direttore") // professore/ricercatore
        window.location.href = "chief.html";
}