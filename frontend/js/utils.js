// file che contiene variabili e funzioni sempre necessarie

// Verifica se l'utente è autenticato, imposta i dati nel localStorage e reindirizza in base al ruolo
function loginVerify(token) {
    if (!localStorage.getItem("token")) {
        localStorage.clear();
        alert("Accesso non effettuato! Redirecting...");
        window.location.href = "index.html";
    } else {
        var role = localStorage.getItem("role");
        var permission = localStorage.getItem("permission");
        if (permission === 1 && window.location.href != "http://localhost/jolly.html") {// jolly
            window.location.href = "jolly.html";
        }
        else if (permission === 0 && role === "base" && window.location.href != "http://localhost/user.html") // professore/ricercatore
            window.location.href = "user.html";
        else if (permission === 0 && role === "amministrativo" && window.location.href != "http://localhost/admin.html") // professore/ricercatore
            window.location.href = "admin.html";
        else if (permission === 0 && role === "direttore" && window.location.href != "http://localhost/chief.html") // professore/ricercatore
            window.location.href = "chief.html";
        // else throw new Exception("È impossibile che esca un errore qua a meno che tu non sia un hachkerz!")
    }
}

// Decodifica il token JWT e restituisce il payload come oggetto
function decodeToken(Token) {
    const arrayToken = Token.split('.');
    return JSON.parse(atob(arrayToken[1]));

}

// Recupera le pratiche dal backend e aggiorna la tabella e la paginazione
function getPratiche(tempToken) {
    token = "Bearer " + tempToken;
    role = localStorage.getItem('role');

    $.ajax({
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': token, // Aggiungi il token all'header
        },
        url: "http://localhost:8080/pratiche/home/",
        dataType: 'json',

        success: function (data) {

            elencoPratiche = data;
            state.tabella = elencoPratiche;

            showPaginationList();

            buildTable();

        },
        error: function (jqXHR, textStatus, errorThrown) {
            
            // console.log("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }


    })
}

// Mostra o nasconde la barra di paginazione in base al numero di pratiche
function showPaginationList() {
    const len = state.tabella.length
    if (len < state.rowsPerPage) {
        const pagination = document.getElementById('pagination-view');
        pagination.style.display = 'none';
    }
    else {
        const pagination = document.getElementById('pagination-view');
        pagination.style.display = 'flex';
    }
}

// Restituisce i dati paginati per la tabella delle pratiche
function pagination(elencoPratiche, page, rowsPerPage) {

    var trimStart = (page - 1) * rowsPerPage    // riga iniziale delle pratiche nella pagina attuale
    var trimEnd = trimStart + rowsPerPage       // riga finale

    var trimmedData = elencoPratiche.slice(trimStart, trimEnd)  // pratiche nella schermata precedente

    var pages = Math.round(elencoPratiche.length / rowsPerPage);

    return {
        'tabella': trimmedData,
        'pages': pages,
    }
}

// Costruisce i pulsanti di paginazione e gestisce il cambio pagina
function pageButtons(pages) {
    var wrapper = document.getElementById('pagination-wrapper')

    wrapper.innerHTML = ``

    var maxLeft = (state.page - Math.floor(state.window / 2))
    var maxRight = (state.page + Math.floor(state.window / 2))

    if (maxLeft < 1) {
        maxLeft = 1
        maxRight = state.window
    }

    if (maxRight > pages) {
        maxLeft = pages - (state.window - 1)

        if (maxLeft < 1) {
            maxLeft = 1
        }
        maxRight = pages
    }

    for (var page = maxLeft; page <= maxRight; page++) {
        wrapper.innerHTML += `<button value=${page} class="page btn btn-sm btn-info">${page}</button>`
    }

    if (state.page != 1) {
        wrapper.innerHTML = `<button value=${1} class="page btn btn-sm btn-info">&#171; First</button>` + wrapper.innerHTML
    }

    if (state.page != pages) {
        wrapper.innerHTML += `<button value=${pages} class="page btn btn-sm btn-info">Last &#187;</button>`
    }

    $('.page').on('click', function () {
        $('#table-body').empty()

        state.page = Number($(this).val())

        buildTable()
    })

}



