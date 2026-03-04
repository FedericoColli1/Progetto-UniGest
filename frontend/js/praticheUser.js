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
        //Keep in mind we are using "Template Litterals to create rows"
        var row = `<tr>
                    <td class="align-middle">${myList[i].Tipologia}</td>
                    <!-- <td class="align-middle">${myList[i].Passaggi}</td> -->
                    <td class="align-middle">${myList[i].DataCreazione}</td>
                    <td class="align-middle">${myList[i].IdPratica}</td>
                    <td class="align-middle">${myList[i].PassaggioAttuale + 1} / ${myList[i].PassaggiMAX}</td>
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
    // document.getElementById("object-name state_ID").innerText = data.Passaggi + ", " + data.PassaggioAttuale + "/" + data.PassaggiMAX;
    document.getElementById("object-name description_ID").innerText = data.Descrizione;
}

// Torna alla lista delle pratiche dalla vista dettaglio e ripristina lo stato
function backToList() {
    document.getElementById('detail-view').style.display = 'none';
    showPaginationList()
    document.getElementById('list-view').style.display = 'flex';
}