// Variabili globali per la gestione delle pratiche e dello stato della paginazione
let elencoPratiche
let trimmedPratiche
let state = {
    tabella: elencoPratiche,
    page: '1',
    rowsPerPage: '10',
    window: '11' // massimo di pulsanti nella barra di navigazione
}

// Costruisce la tabella delle pratiche paginata e popola la barra di navigazione
function buildTable() {
    var table = $('#table-body')

    var data = pagination(state.tabella, state.page, state.rowsPerPage)
    var myList = data.tabella

    for (var i = 0 in myList) {
        var row = `<tr>
                    <td class="align-middle">${myList[i].Tipologia}</td>
                    <!-- <td class="align-middle">${myList[i].Passaggi}</td> -->
                    <td class="align-middle">${myList[i].DataCreazione}</td>
                    <td class="align-middle">${myList[i].IdPratica}</td>
                    <td class="align-middle">${myList[i].NPassaggio + 1} / ${myList[i].NPassaggi}</td>
                    <td class="text-center"><button class="btn btn-success btn-sm " onclick="showDetail(${i})">
                        Visualizza  
                    </button></td>
                   </tr>
                  `
        table.append(row)
    }

    pageButtons(data.pages)
}

// Mostra i dettagli di una pratica selezionata e aggiorna la vista
function showDetail(index) {
    var data = pagination(state.tabella, state.page, state.rowsPerPage)
    var myList = data.tabella
    data = myList[index];
    document.getElementById('list-view').style.display = 'none';
    document.getElementById('pagination-view').style.display = 'none';
    document.getElementById('detail-view').style.display = 'flex';

    document.getElementById("object-name title_ID").innerText = data.Tipologia;
    document.getElementById("object-name ID_ID").innerText = data.IdPratica;
    document.getElementById("object-name data_ID").innerText = data.DataCreazione;
    // document.getElementById("object-name state_ID").innerText = data.Passaggi + ", " + data.NPassaggio + "/" + data.PassaggiMAX;
    document.getElementById("object-name description_ID").innerText = data.Descrizione;
    document.getElementById("object-name documenti_richiesti").innerText = data.ListDocRichiesti;

    const documentiUscita = data.ListDocUscita.split(',');

    var links = ""

    documentiUscita.forEach((doc) => {
        links += `<span onclick="getFile(${data.IdPratica}, ${data.NPassaggio}, '${encodeURIComponent(doc)}')">${doc}</span><br>`;
    });

    document.getElementById("object-name documenti_precedenti").innerHTML = links;

    localStorage.setItem('IdPratica', data.IdPratica);
    localStorage.setItem('NPassaggio', data.NPassaggio);
}

// Torna alla lista delle pratiche dalla vista dettaglio e ripristina lo stato
function backToList() {
    document.getElementById('detail-view').style.display = 'none';
    showPaginationList()
    document.getElementById('list-view').style.display = 'flex';
    localStorage.removeItem('IdPratica');
    localStorage.removeItem('NPassaggio');
}

// Simula il click sull'input file per caricare un nuovo allegato
function caricaNuovoAllegato() {
    document.getElementById('fileInput').click();
}

// Gestisce la selezione dei file e l'invio degli allegati tramite AJAX
function fileSelezionati(event) {
    const files = event.target.files;


    if (files.length > 0) {
        var fileDaMandare = new FormData();

        fileDaMandare.append('IdPratica', localStorage.getItem('IdPratica'));
        fileDaMandare.append('NPassaggio', localStorage.getItem('NPassaggio'));
        for (let i = 0; i < files.length; i++) {
            fileDaMandare.append('files[]', files[i]);

        }

        $.ajax({

            url: 'http://localhost:8080/pratiche/add',
            type: 'POST',
            data: fileDaMandare,
            contentType: false,
            processData: false,

            headers: {
                'Authorization': getToken(),
            },

            success: function (response) {
                window.location.reload();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
            }

        })
        aggiornaAllegati();
    }
}

// Scarica un file allegato relativo a una pratica tramite chiamata fetch
function getFile(IdPratica, NPassaggio, doc) {
    url = `http://localhost:8080/pratiche/getFile?IdPratica=${IdPratica}&NPassaggio=${NPassaggio}&File=${encodeURIComponent(doc)}`;
    fetch(url, {
        method: "GET",
        headers: {
            "Authorization": getToken()
        }
    })
        .then(res => res.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = doc; // oppure prendi il nome dinamico
            document.body.appendChild(a);
            a.click();
            a.remove();
        });

}