CREATE DATABASE IF NOT EXISTS unigest;
use unigest;

CREATE TABLE IF NOT EXISTS Utente(
    Id int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Nome varchar(255) not null,
    Mail varchar(255) not null,
    Pwd varchar(255) not null,
    DataCreazione datetime NOT NULL DEFAULT current_timestamp(),
    #tipe?
    Jolly boolean DEFAULT 0,
    UNIQUE(Mail, DataCreazione)
);

CREATE TABLE IF NOT EXISTS Docente(
    IdDocente int(5) NOT NULL PRIMARY KEY,
    Corso varchar(255) NOT NULL,
    FOREIGN KEY (IdDocente) REFERENCES Utente(Id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Ricercatore(
    IdRicercatore int(5) NOT NULL PRIMARY KEY,
    Ricerca varchar(255) NOT NULL,
    FOREIGN KEY (IdRicercatore) REFERENCES Utente(Id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS UOperativa(
    IdUnita int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Nome Text(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Amministrativo(
    IdAmministrativo int(5) NOT NULL PRIMARY KEY,
    IdUnita int(5) NOT NULL,
    Direttore boolean DEFAULT 0,
    FOREIGN KEY (IdAmministrativo) REFERENCES Utente(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (idUnita) REFERENCES UOperativa(IdUnita) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Tipologia(
    Tipologia VARCHAR(255) NOT NULL PRIMARY KEY,
    NPassaggi int(2) NOT NULL,
    Passaggi JSON,
    Inizio VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Pratica(
    IdPratica int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdDocente int(5) NOT NULL,
    Descrizione Text(65535) NOT NULL,
    Tipologia VARCHAR(255) NOT NULL,
    DataCreazione datetime NOT NULL DEFAULT current_timestamp(),
    Codice VARCHAR(255) DEFAULT 0,
    FOREIGN KEY (IdDocente) REFERENCES Utente(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Tipologia) REFERENCES Tipologia(Tipologia) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Passaggio(
    IdPassaggio int(5) AUTO_INCREMENT PRIMARY KEY,
    NPassaggio int(3) DEFAULT 1,
    IdPratica int(5),
    IdUnita int(5),
    #i doc li mettiamo come tabella aggiunta che ha come id il percorso del documento?
    ListDocUscita Text(65535) NOT NULL,
    ListDocRichiesti Text(65535) NOT NULL,
    Terminato boolean DEFAULT 0,
    FOREIGN KEY (IdPratica) REFERENCES Pratica(IdPratica) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (IdUnita) REFERENCES UOperativa(IdUnita) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Assegnazione(
    IdAmministrativo int(5) NOT NULL,
    IdPassaggio int(5) NOT NULL,
    PRIMARY KEY (IdAmministrativo, IdPassaggio),
    FOREIGN KEY (IdAmministrativo) REFERENCES Amministrativo(IdAmministrativo) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (IdPassaggio) REFERENCES Passaggio(IdPassaggio) ON DELETE CASCADE ON UPDATE CASCADE
);


-- Inserimento dati nella tabella Utente
INSERT INTO Utente (Id, Nome, Mail, Pwd, Jolly) VALUES
(1, 'Mario Rossi', 'mario.rossi@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(2, 'Luigi Bianchi', 'luigi.bianchi@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(3, 'Anna Verdi', 'anna.verdi@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(4, 'Giulia Neri', 'giulia.neri@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(5, 'Marco Gialli', 'marco.gialli@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(6, 'Sara Blu', 'sara.blu@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(7, 'Paolo Verde', 'paolo.verde@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(8, 'Laura Viola', 'laura.viola@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(9, 'Francesco Nero', 'francesco.nero@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(10, 'Elena Rosa', 'elena.rosa@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 0),
(11, 'Maria Rossa', 'maria.rossa@example.com', '$2y$10$27JEqn1ykaSa2WWMdq.N7OVLGww/MGvGmRYxQl2x9OTnmhQF2DcTi', 1);

-- Inserimento dati nella tabella Docente
INSERT INTO Docente (IdDocente, Corso) VALUES
(7, 'Matematica'),
(8, 'Fisica'),
(9, 'Chimica'),
(10, 'Informatica');

-- Inserimento dati nella tabella UOperativa
INSERT INTO UOperativa (IdUnita, Nome) VALUES
(1, 'Unita Operativa 1'),
(2, 'Unita Operativa 2');

-- Inserimento dati nella tabella Amministrativo
INSERT INTO Amministrativo (IdAmministrativo, IdUnita, Direttore) VALUES
(1, 1, 1),
(2, 2, 1),
(3, 1, 0),
(4, 1, 0),
(5, 2, 0),
(6, 2, 0);

-- Inserimento dati nella tabella Tipologia
-- Aggiungere nel JSON_array i file richiesti per ogni passaggio
INSERT INTO Tipologia (Tipologia, NPassaggi, Passaggi, Inizio) VALUES
('Tipo A', 3, '{"0":"0;1;file3.txt,file4.txt;","1":"1;2;file5.txt,file6.txt;","2":"2;1;file7.txt,file8.txt;"}','file1.txt,file2.txt'),
('Tipo B', 2, '{"0":"0;2;file3.txt,file4.txt;","1":"1;1;file5.txt,file6.txt;"}', 'file7.txt,file8.txt'),
('Tipo C', 4, '{"0":"0;2;file3.txt,file4.txt;","1":"1;1;file5.txt,file6.txt;","2":"2;2;file7.txt,file8.txt;","3":"3;1;file9.txt,file10.txt;"}','file1.txt,file2.txt'),
('Tipo D', 3, '{"0":"0;2;file3.txt,file4.txt;","1":"1;1;file5.txt,file6.txt;","2":"2;2;file7.txt,file8.txt;"}','file1.txt,file2.txt');


GRANT ALL PRIVILEGES ON unigest.* TO 'user'@'%';
FLUSH PRIVILEGES;