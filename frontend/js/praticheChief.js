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

    id_prec = null;
    for (var i = 0 in myList) {
        if (i != 0) id_prec = myList[i - 1].IdPratica;
        if (!id_prec || id_prec != myList[i].IdPratica) {
            var row = `<tr>
                            <td class="align-middle">${myList[i].Tipologia}</td>
                    <!-- <td class="align-middle">${myList[i].Passaggi}</td> -->
                            <td class="align-middle">${myList[i].DataCreazione}</td>
                            <td class="align-middle">${myList[i].IdPratica}</td>
                    <td class="align-middle">${myList[i].NPassaggio + 1} / ${myList[i].NPassaggi}</td>
                        ${isAssigned(i, myList[i].Azione)}  
                   </tr>
                  `
            table.append(row)
        }
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
    document.getElementById('listAmministrativi').style.display = 'flex';



    document.getElementById("object-name title_ID").innerText = data.Tipologia;
    document.getElementById("object-name ID_ID").innerText = data.IdPratica;
    document.getElementById("object-name data_ID").innerText = data.DataCreazione;
    // document.getElementById("object-name state_ID").innerText = data.Passaggi + ", " + data.NPassaggio + "/" + data.PassaggiMAX;
    document.getElementById("object-name description_ID").innerText = data.Descrizione;
    document.getElementById("object-name documenti_richiesti").innerText = data.ListDocRichiesti;
    document.getElementById("object-name documenti_caricati").innerText = data.ListDocUscita;

    localStorage.setItem('IdPratica', data.IdPratica);
    localStorage.setItem('NPassaggio', data.NPassaggio);

    if (data.Azione == "Assegnato")
        getAmministrativiACuiEAssegnataLaPratica(data.IdPratica, data.NPassaggio);
    else
        getAmministrativi(data.IdPratica, data.NPassaggio);


}

// Torna alla lista delle pratiche dalla vista dettaglio e ripristina lo stato
function backToList() {
    document.getElementById('detail-view').style.display = 'none';
    document.getElementById('listAmministrativi').style.display = 'none';
    showPaginationList()
    document.getElementById('list-view').style.display = 'flex';
    document.getElementById('listAmministrativi').innerHTML = '';
    localStorage.removeItem('IdPratica');
    localStorage.removeItem('NPassaggio');
}

// function caricaNuovoAllegato() {
//     document.getElementById('fileInput').click();
// }

// function fileSelezionati(event) {
//     const files = event.target.files;


//     if (files.length > 0) {
//         var fileDaMandare = new FormData();

//         fileDaMandare.append('IdPratica', localStorage.getItem('IdPratica'));
//         fileDaMandare.append('NPassaggio', localStorage.getItem('NPassaggio'));
//         for (let i = 0; i < files.length; i++) {
//             fileDaMandare.append('files[]', files[i]);

//         }

//         $.ajax({

//             url: 'http://localhost:8080/pratiche/add',
//             type: 'POST',
//             data: fileDaMandare,
//             contentType: false,
//             processData: false,

//             headers: {
//                 'Authorization': getToken(),
//             },

//             success: function (response) {
//                 console.log('Successo:', response);
//             },
//             error: function (jqXHR, textStatus, errorThrown) {
//                 alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
//             }

//         })



//     }


// }

// Restituisce il bottone corretto (Assegna o Visualizza) in base allo stato della pratica
function isAssigned(i, stato) {

    if (stato == 'Da Assegnare') {
        return `<td class="text-center">
                        <button class="btn btn-danger btn-sm" onclick="showDetail(${i})">Assegna</button>
                    </td>`;
    } else {
        return `<td class="text-center">
                        <button class="btn btn-success btn-sm" onclick="showDetail(${i})">Visualizza</button>
                    </td>`;
    }

}

// Recupera gli amministrativi a cui è già assegnata la pratica e costruisce la lista
function getAmministrativiACuiEAssegnataLaPratica(IdPratica, NPassaggio) {

    $.ajax({
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': getToken(), // Aggiungi il token all'header
        },
        url: "http://localhost:8080/pratiche/assegna?IdPratica=" + IdPratica + '&NPassaggio=' + NPassaggio, ////////////////////////////////////////////////////////////////////////////////////////////////////////
        dataType: 'json',
        // data: JSON.stringify({IdPratica: IdPratica, NPassaggio: NPassaggio}),

        success: function (data) {
            costruisciAssegnazione(data);

        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }
    })
}

// Costruisce la lista degli amministrativi assegnati a una pratica
function costruisciAssegnazione(data) {
    var lista = document.getElementById('listAmministrativi');

    lista.innerHTML = ` <h6>Amministrativi assegnati a questo passaggio:</h6>
                        <ul>`;

    data.forEach(email => {
        lista.innerHTML += `<li>${email.Mail}</li>`;
    });
    lista.innerHTML += `</ul>`;

}

// Recupera tutti gli amministrativi disponibili per l'assegnazione
function getAmministrativi(IdPratica, NPassaggio) {
    datiDaMandare = [];
    datiDaMandare['IdPratica'] = IdPratica;
    datiDaMandare['NPassaggio'] = NPassaggio;
    var amministrativoAttuale;

    $.ajax({
        url: "http://localhost:8080/pratiche/assegna/",
        type: 'GET',

        headers: {
            'Authorization': getToken(),
        },
        success: function (amministrativi) {
            createAssegnatiTable(JSON.parse(amministrativi), IdPratica, NPassaggio);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }
    });
    return amministrativoAttuale;
}

// Costruisce la tabella di selezione degli amministrativi e gestisce l'invio della selezione
function createAssegnatiTable(amministrativi, IdPratica, NPassaggio) {
    if (amministrativi) {
        var lista = document.getElementById('listAmministrativi');

        lista.innerHTML += `
            <h5>Seleziona gli amministrativi:</h5>`;

        index = 0;

        amministrativi.forEach(amm => {
            lista.innerHTML += `<div class="form-check ms-4">
            <input class="form-check-input" type="checkbox" id="check_${index}" value="${amm.Mail}">
            <label class="form-check-label" for="check_${index}">${amm.Mail}</label></div>
            `;
            index++;


        });
        lista.innerHTML += `<div class="mt-3 ms-4">
            <button id="submitCheckboxes" class="btn btn-primary">Invia selezione</button>
        </div>`;

        sendAmministrativiSelezionati(amministrativi, IdPratica, NPassaggio);
    }
}

// Gestisce l'invio degli amministrativi selezionati tramite AJAX
function sendAmministrativiSelezionati(amministrativi, IdPratica, NPassaggio) {
    document.getElementById("submitCheckboxes").addEventListener("click", function () {
        const checkboxes = document.querySelectorAll("#listAmministrativi .form-check-input:checked");
        if (checkboxes.length != 0) {
            const daInviare = [];
            const Mails = [];
            checkboxes.forEach(box => {
                Mails.push(box.value);
            });
            daInviare['IdPratica'] = IdPratica;
            daInviare['NPassaggio'] = NPassaggio;

            daInviare['Mail'] = Mails;

            $.ajax({
                url: 'http://localhost:8080/pratiche/assegna/',
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({ IdPratica: IdPratica, NPassaggio: NPassaggio, Mail: Mails }),
                headers: {
                    "Authorization": getToken() // se serve autenticazione
                },
                success: function (response) {
                    alert("Pratica assegnata correttamente!");
                    window.location.reload();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
                }
            });

        }
        else {
            event.preventDefault();
            alert("Non hai selezionato nessuna mail");
            // sendAmministrativiSelezionati(amministrativi, IdPratica, NPassaggio);
        }
    });
}

