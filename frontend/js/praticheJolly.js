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
        var row = `<tr>
                        <td class="align-middle">${myList[i].Tipologia}</td>
                    <!-- <td class="align-middle">${myList[i].Passaggi}</td> -->
                        <td class="align-middle">${myList[i].DataCreazione}</td>
                        <td class="align-middle">${myList[i].IdPratica}</td>
                    <td class="align-middle">${myList[i].NPassaggio + 1} / ${myList[i].NPassaggi}</td>
                    ${isAssigned(i, myList[i].Azione, myList[i].Codice)}  
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
    document.getElementById('listAmministrativi').style.display = 'flex';

    document.body.style.overflowY = "auto";


    document.getElementById("object-name title_ID").innerText = data.Tipologia;
    document.getElementById("object-name ID_ID").innerText = data.IdPratica;
    document.getElementById("object-name data_ID").innerText = data.DataCreazione;
    // document.getElementById("object-name state_ID").innerText = data.Passaggi + ", " + data.NPassaggio + "/" + data.PassaggiMAX;
    document.getElementById("object-name description_ID").innerText = data.Descrizione;
    if (data.Terminato == 1) {

        const documentiUscita = data.ListDocUscita.split(',');

        var links = ""

        documentiUscita.forEach((doc) => {
            links += `<span onclick="getFile(${data.IdPratica}, ${data.NPassaggio}, '${encodeURIComponent(doc)}')">${doc}</span><br>`;
        });

        document.getElementById("object-name documenti_precedenti").innerHTML = links;

        localStorage.setItem('IdPratica', data.IdPratica);
        localStorage.setItem('NPassaggio', data.NPassaggio);
    }
    else {
        document.getElementById("object-name documenti_richiesti").innerHTML = data.ListDocRichiesti;

        const documentiUscita = data.ListDocUscita.split(',');

        var links = ""

        documentiUscita.forEach((doc) => {
            links += `<span onclick="getFile(${data.IdPratica}, ${data.NPassaggio}, '${encodeURIComponent(doc)}')">${doc}</span><br>`;
        });

        document.getElementById("object-name documenti_precedenti").innerHTML = links;

        localStorage.setItem('IdPratica', data.IdPratica);
        localStorage.setItem('NPassaggio', data.NPassaggio);
    }

    localStorage.setItem('IdPratica', data.IdPratica);
    localStorage.setItem('NPassaggio', data.NPassaggio);

    if (data.Azione == "Assegnato")
        getAmministrativiACuiEAssegnataLaPratica(data.IdPratica, data.NPassaggio);
    else
        getAmministrativi(data.IdPratica, data.NPassaggio);


    // if(data.allegati) {/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //     const allegatiContainer = document.getElementById("allegati");
    //     // allegatiContainer.innerHTML = '';
    //     data.allegati.forEach(attachment => {
    //         const imgElement = document.createElement("img");
    //         imgElement.src = attachment;
    //         imgElement.classList.add("rounded", "singleAllegato");
    //         allegatiContainer.appendChild(imgElement);
    //     });}

}

// Torna alla lista delle pratiche dalla vista dettaglio e ripristina lo stato
function backToList() {
    document.getElementById('detail-view').style.display = 'none';
    document.getElementById('listAmministrativi').style.display = 'none';
    showPaginationList();
    document.getElementById('list-view').style.display = 'flex';
    document.getElementById('listAmministrativi').innerHTML = '';
    localStorage.removeItem('IdPratica');
    localStorage.removeItem('NPassaggio');
}

// Restituisce il bottone corretto (Assegna, Visualizza o Terminata) in base allo stato della pratica
function isAssigned(i, stato, codice) {

    if (codice != 0) {
        return `<td class="text-center">
                        <button class="btn btn-light btn-sm">Terminata: ${codice}</button>
                    </td>`;
    } else if (stato == 'Da Assegnare') {
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
        url: "http://localhost:8080/pratiche/assegna?IdPratica=" + IdPratica + '&NPassaggio=' + NPassaggio + '&Assegnato=1', ////////////////////////////////////////////////////////////////////////////////////////////////////////
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
    var datiDaMandare = [];
    datiDaMandare['IdPratica'] = IdPratica;
    datiDaMandare['NPassaggio'] = NPassaggio;
    var amministrativoAttuale;

    $.ajax({
        url: "http://localhost:8080/pratiche/assegna?IdPratica=" + IdPratica + '&NPassaggio=' + NPassaggio,
        type: "GET",

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

            // Esegui chiamata AJAX al backend
            $.ajax({
                url: 'http://localhost:8080/pratiche/assegna/',
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify({ IdPratica: IdPratica, NPassaggio: NPassaggio, Mail: Mails }),
                headers: {
                    "Authorization": getToken() // se serve autenticazione
                },
                success: function (response) {
                    // alert("Pratica assegnata correttamente!");
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
    }
}


// Mostra l'overlay di conferma per l'eliminazione di una pratica
function apriOverlayConfermaEliminazione() {
    document.getElementById('conferma_eliminazione').style.display = 'flex';
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        const value = localStorage.getItem(key);
    }

}

// Chiude l'overlay di conferma eliminazione
function chiudiOverlayConfermaEliminazione() {
    document.getElementById('conferma_eliminazione').style.display = 'none';
}

// Conferma l'eliminazione della pratica selezionata tramite chiamata AJAX
function confermaEliminazione() {

    IdPratica = localStorage.getItem('IdPratica');

    $.ajax({
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': getToken(), // Aggiungi il token all'header
        },
        url: "http://localhost:8080/pratiche/cancellazione",
        data: JSON.stringify({ IdPratica: IdPratica }),


        success: function (data) {
            alert("Pratica eliminata con successo!");
            window.location.reload();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }
    });
}

// Mostra l'overlay di conferma per la terminazione di una pratica
function apriOverlayConfermaTerminazione() {
    document.getElementById('conferma_terminazione').style.display = 'flex';
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        const value = localStorage.getItem(key);
    }

}

// Chiude l'overlay di conferma terminazione
function chiudiOverlayConfermaTerminazione() {
    document.getElementById('conferma_terminazione').style.display = 'none';
}

// Conferma la terminazione della pratica selezionata tramite chiamata AJAX
function confermaTerminazione() {

    IdPratica = localStorage.getItem('IdPratica');

    var codice = $('input[name="codice"]:checked').val();

    if (!codice) {
        alert("Seleziona un motivo prima di terminare.");
        return;
    }

    $.ajax({
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': getToken(), // Aggiungi il token all'header
        },
        url: "http://localhost:8080/pratiche/cancellazione",                    // DA METTEREEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE
        data: JSON.stringify({ IdPratica: IdPratica, Codice: codice }),


        success: function (data) {
            alert("Pratica terminata con successo!");
            window.location.reload();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            alert("Error " + jqXHR.status + ": " + jqXHR.responseText);
        }
    });
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





