// Al caricamento del documento, richiama le funzioni per ottenere le tipologie e mostrare i file necessari
$(document).ready(function () {
    getTipologieEFileInizialiRichiesti();

    mostraPraticheNecessarie();

    inizializzaDashboardNavbar();
})

// Gestisce l'invio del form, impedendo il comportamento di default e inviando i dati tramite AJAX
$("form").submit(function (e) {
    e.preventDefault();
    let reqType = $("#inputGroupSelect01").val();
    let content = $("#descrizione").val();
    sendData(reqType, content);
})

// Invia i dati del form (tipologia, descrizione e file) al backend tramite AJAX
function sendData(type, content) {

    if (content.trim() === "") {
        alert('Descrizione non valida!');
    } else if (type == 0) {
        alert("Tipologia non selezionata!");
    } else {
        const fileInput = document.getElementById('fileInput');

        var fileDaMandare = new FormData();

        fileDaMandare.append('Tipologia', type);
        fileDaMandare.append('Descrizione', content);
        for (let i = 0; i < fileInput.files.length; i++) {
            fileDaMandare.append('files[]', fileInput.files[i]);
        }

        $.ajax({
            type: "POST",
            // url: "http://localhost:8080/pratiche/add/",
            url: "http://localhost:8080/pratiche/createPratica/",

            headers: {
                'Authorization': getToken(), // Aggiungi il token all'header
            },
            data: fileDaMandare,
            contentType: false,
            processData: false,

            success: function (res) {
                alert("L'operazione è andata a buon fine!");
                window.location.reload();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
            }
        })
    }
}

// Recupera le tipologie di pratica e i file iniziali richiesti dal backend e popola il dropdown
function getTipologieEFileInizialiRichiesti() {
    $.ajax({
        type: "GET",
        url: "http://localhost:8080/pratiche/getType",
        headers: {
            'Authorization': getToken(), // Aggiungi il token all'header
        },
        contentType: false,
        processData: false,

        success: function (res) {
            temp = JSON.parse(res);

            dropdown = document.getElementById('inputGroupSelect01')
            dropdown.innerHTML += '<option value="0">Seleziona un valore...</option>'
            Object.entries(temp).forEach(([key, value]) => {
                localStorage.setItem(value.Tipologia, value.Inizio);

                mostraTipologieDiPratica(value.Tipologia, dropdown);
            });

        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }
    })
}

// Aggiunge una tipologia di pratica come opzione al dropdown
function mostraTipologieDiPratica(temp, dropdown) {
    dropdown.innerHTML += '<option value="' + temp + '">' + temp + '</option>';
}

// Mostra i file necessari in base alla tipologia selezionata
function mostraPraticheNecessarie() {
    var select = document.getElementById('inputGroupSelect01');

    var valoreSelezionato = select.value;

    var contenuto = localStorage.getItem(valoreSelezionato);

    var divOutput = document.getElementById('fileNecessari');

    divOutput.innerHTML = contenuto ? "File necessari: " + contenuto : "Nessun file necessario trovato.";
}

// Aggiungo l'evento al select (così la funzione viene chiamata quando cambia)
document.getElementById('inputGroupSelect01').addEventListener('change', mostraPraticheNecessarie);

// Script per mostrare il nome del file selezionato
document.getElementById('fileInput').addEventListener('change', function () {
    const fileDisplay = document.getElementById('fileNameDisplay');
    if (this.files.length > 0) {
        let fileNames = Array.from(this.files).map(file => file.name).join(', ');
        fileDisplay.textContent = fileNames;
    } else {
        fileDisplay.textContent = 'Nessun file selezionato';
    }
});

// Gestisce la selezione di una voce dal dropdown personalizzato e aggiorna il valore e il testo del bottone
document.querySelectorAll('#dropdownMenuList1 .dropdown-item').forEach(function (item) {
    item.addEventListener('click', function (e) {
        e.preventDefault();

        // Imposta il testo nel button
        document.getElementById('dropdownMenuButton1').textContent = this.textContent;

        // Imposta il valore del dropdown
        document.getElementById('tipoRichiestaInput').value = this.getAttribute('data-value');
    });
});

function inizializzaDashboardNavbar() {
    const role = localStorage.getItem("role");
    const link = document.getElementById("linkDasboardDinamico");
    const permission = localStorage.getItem("permission");

    if (permission == 0 && role == "base") {
        link.innerHTML = `<a href="http://localhost/user.html">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                `;
    } else if (permission == 1) {
        link.innerHTML = `<a href="http://localhost/jolly.html">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                `;
    }
}