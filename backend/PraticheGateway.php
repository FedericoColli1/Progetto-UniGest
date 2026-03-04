<?php

use PhpParser\Node\Name;
use SebastianBergmann\Environment\Console;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: *");

require_once 'Imports/Class/Jolly.php';
require_once 'jwt/vendor/autoload.php';
require_once 'Imports/Database.php';
require_once 'Imports/DocumentHandler.php';
require_once 'Imports/Class/Amministrativo.php';
require_once 'Imports/Class/User.php';
require_once 'Imports/ControllerJwt.php';
require_once 'Imports/Class/Direttore.php';
require_once 'Imports/Class/Tipologia.php';
require_once 'Imports/Download.php';

/**
 * @class PraticheGateway
 *  Oggeto PraticheGateway che ha come scopo quello di gestire le richieste che hanno come path 'pratiche/...'
 */
class PraticheGateway extends Gateway
{

    private $DocHandler;
    
    /**
     * La funzione serve per gestire le richieste inviate a PraticheGateway da parte di Controller.
     * In base alla seconda parte dell'uri, indicata in parts[2] verifica che tipo di richeista e' tramite il REQUEST_METHOD e gestisce i dati ottenuti da  ControllerJwt
     * per verificare l'identita e il rango dell'utente che ha inviato la richiesta.
     * Gli if/else presenti all'interno della funzione controllano principalmente queste quattro situazioni:
     *      -Controllo dei permessi dell'utente che fa la richiesta
     *      -Che cosa sta richiedendo l'utente
     *      -Esistenza dei file/valori richiesti e da restituire
     *      -Controllo delle eccezzioni 
     * 
     * $cleanUrl=="home" (GET)
     * Questo blocco gestisce le richieste per la pagina "home". In base al ruolo e ai permessi dell'utente
     * @retval 208  $pratiche
     * 
     * $cleanUrl == "getType" (GET)
     * Questo blocco gestisce le richieste per ottenere i tipi di pratiche
     * @retval 208  $results
     * 
     * $cleanUrl == "getFile" (GET)
     * Questo blocco è responsabile del download dei file caricati nei passaggi
     * @retval 404  ["error" => "Missing pratica passaggio o file"]
     * 
     * $cleanUrl=="add" (POST)
     * Questo blocco gestisce l'aggiunta di documenti a una pratica
     * @retval 201  ["success" => "Documenti caricati con successo"]
     * @retval 400   ["error" => "Nessun file inviato o file errato"]
     * @retval 401   ["error" => "Non autorizzato"]
     * 
     * $cleanUrl == "createPratica" (POST)
     * Questo blocco si occupa della creazione di nuove pratiche
     * @retval 404  ["error" => "Missing tipologia or description"]
     * 
     * $cleanUrl=="assegna" (GET)
     * Questo blocco gestisce l'assegnazione dei passaggi agli amministrativi
     * @retval 404  ["error" => "Missing IdPratica or NPassaggio"]
     * 
     * $cleanUrl == "cancellazione" (DELETE)
     * Questo blocco gestisce la cancellazione di una pratica
     * @retval 404   ["error" => $input['IdPratica']]
     * @retval 200   ["eliminato" => "eliminato con successo"]
     * @retval 400   ["eliminato" => "eliminazione fallita"]
     * 
     * $cleanUrl == "cancellazione" (PUT)
     * Questo blocco gestisce la terminazione forzata di una pratica
     * @retval 200   ["eliminato" => "eliminato con successo"]
     * @retval 400   ["eliminato" => "eliminazione fallita"]
     * 
     * @param array $parts vettore che contiene il percorso nell'url
     * @param array $dati Contiene l'elenco delle informazioni sull'utente prese dal token
     * @param array $parsedUrl Arrai che ha come elementi il path e i valori di variabili passati con l'url
     * @param string $cleanUrl parte di path necessaria per capire la richiesta che si è fatta al server
     */
    public function handle_request($parts)
    {
        $dati = ControllerJwt::control();

        try {
            
            $parsedUrl = parse_url($parts[2]);

            $cleanUrl = $parsedUrl['path'];

            if ($cleanUrl == "home") {
                if ($_SERVER['REQUEST_METHOD'] === "GET") {
                    $pratiche = null;
                    if ($dati["role"] == "amministrativo" && $dati["permission"] == 0) {
                        $user = new Amministrativo($dati["sub"]);
                        $pratiche = $user->getPraticheAmministrativo();
                    } elseif ($dati["role"] == "base" && $dati["permission"] == 0) {

                        $user = new User($dati["sub"]);
                        $pratiche = $user->getPratiche();

                    } elseif ($dati["role"] == "direttore" && $dati["permission"] == 0) {
                        $user = new Direttore($dati["sub"]);
                        $pratiche = $user->getPraticheDirettore();
                    }

                    if ($dati["permission"] == 1) {
                        error_log($dati["sub"]);
                        $user = new Jolly($dati["sub"]);
                        $pratiche = $user->getPraticheJolly();
                    }

                    if (!is_null($pratiche)) {

                        http_response_code(208);
                        echo json_encode($pratiche);

                    }

                }

            }

            if ($cleanUrl == "getType") {
                if ($_SERVER['REQUEST_METHOD'] === "GET") {
                    $results = Tipologia::getTipologie();
                    if ($results != null) {
                        http_response_code(208);
                        echo json_encode($results);
                    }
                }
            }
            if ($cleanUrl == "getFile") {
                if ($_SERVER['REQUEST_METHOD'] === "GET") {
                    $pratica = $_GET['IdPratica'];
                    $passaggio = $_GET['NPassaggio'];
                    $file = basename($_GET['File']);
                    if (isset($pratica) && isset($passaggio) && isset($file)) {
                        Download::DownloadFile();
                    } else {
                        http_response_code(404);
                        echo json_encode(["error" => "Missing pratica passaggio o file"]);
                    }
                }
            }

            if ($cleanUrl == "add") {
                if ($_SERVER['REQUEST_METHOD'] === "POST") {
                    if ($dati["role"] == "amministrativo" || $dati["permission"] == 1) {
                        if (!isset($_POST["IdPratica"]) || !isset($_POST["NPassaggio"])) {
                            http_response_code(404);
                            echo json_encode(["error" => "Missing Pratica or Passaggio"]);
                            error_log($_POST["IdPratica"] . " " . $_POST["NPassaggio"]);
                            return;
                        } else {
                            $pratica = $_POST["IdPratica"];
                            $passaggio = $_POST["NPassaggio"];
                            $result = Passaggio::CaricaDocumenti($pratica, $passaggio);
                            if ($result == 201) {
                                http_response_code(201);
                                echo json_encode(["success" => "Documenti caricati con successo"]);
                            } elseif ($result == 400) {
                                http_response_code(400);
                                echo json_encode(["error" => "Nessun file inviato o file errato"]);
                            }
                        }
                    } else {
                        http_response_code(401);
                        echo json_encode(["error" => "Non autorizzato"]);
                    }
                }
            }

            if ($cleanUrl == "createPratica") {
                if ($_SERVER['REQUEST_METHOD'] === "POST") {
                    if ($dati["role"] == "base" || $dati['permission'] == 1) {
                        $user = new Jolly($dati["sub"]);
                        // $input = json_decode(file_get_contents("php://input"), true);
                        if (!isset($_POST["Tipologia"]) || !isset($_POST["Descrizione"])) {
                            http_response_code(404);
                            echo json_encode(["error" => "Missing tipologia or description"]);
                            return;

                        }

                        $tipologia = $_POST["Tipologia"];
                        $descrizione = $_POST["Descrizione"];
                        // check $_FILE per creazione pratica
                        $user->addPratica([$tipologia, $descrizione]);
                    }
                }
            }

            if ($cleanUrl == "assegna") {
                if ($_SERVER['REQUEST_METHOD'] === "GET") {
                    if ($dati["permission"] == 0 && $dati["role"] == "direttore") {
                        $user = new Direttore($dati["sub"]);
                        if (!isset($_GET["IdPratica"]) && !isset($_GET["NPassaggio"])) {
                            $user->getAmministrativi();
                            $input = json_decode(file_get_contents("php://input"), true);
                        } else {
                            $Pratica = $_GET["IdPratica"];
                            $Passaggio = $_GET["NPassaggio"];
                            $user->getAmministrativiAssegnati($Pratica, $Passaggio);
                            $input = json_decode(file_get_contents("php://input"), true);
                        }
                    } else {
                        $user = new Jolly($dati["sub"]);
                        $IdPratica = $_GET["IdPratica"];
                        $NPassaggio = $_GET["NPassaggio"];
                        if (isset($IdPratica) && isset($NPassaggio)) {
                            error_log($IdPratica . " " . $NPassaggio);
                            if (empty($_GET["Assegnato"])) {
                                $user->getAmministrativi($IdPratica, $NPassaggio);
                                $input = json_decode(file_get_contents("php://input"), true);

                            } else {
                                $user->getAmministrativiAssegnati($IdPratica, $NPassaggio);
                                $input = json_decode(file_get_contents("php://input"), true);
                            }
                        } else {
                            http_response_code(404);
                            echo json_encode(["error" => "Missing IdPratica or NPassaggio"]);
                            return;
                        }
                    }
                }
                if ($_SERVER['REQUEST_METHOD'] === "POST") {
                    if ($dati["role"] == "direttore" || $dati["permission"] == "1") {
                        $input = json_decode(file_get_contents("php://input"), true);
                        // error_log($input["NPassaggio"]);
                        if (!isset($input["NPassaggio"]) || !isset($input["IdPratica"]) || !isset($input["Mail"])) {
                            http_response_code(404);
                            echo json_encode(["error" => $input['NPassaggio']]);
                            return;
                        } else {
                            $NPassaggio = $input["NPassaggio"];
                            $Mail = $input["Mail"];
                            $IdPratica = $input["IdPratica"];
                            for ($i = 0; $i < sizeof($Mail); $i++) {
                                $amministrativo = Amministrativo::getIdFromMail($Mail[$i]);
                                if ($amministrativo == null)
                                    throw new Exception("Amministrativo inesistente");
                                else
                                    Direttore::Assegnazione(Passaggio::GetIdPassaggioFromNum($IdPratica, $NPassaggio), $amministrativo);
                            }
                        }
                    }
                }
            }
            if ($cleanUrl == "cancellazione") {
                if ($_SERVER['REQUEST_METHOD'] === "DELETE") {
                    if ($dati["permission"] == 1) {
                        $input = json_decode(file_get_contents("php://input"), true);

                        if (!isset($input["IdPratica"])) {
                            http_response_code(404);
                            echo json_encode(["error" => $input['IdPratica']]);
                            return;
                        } else {
                            $IdPratica = $input["IdPratica"];
                            $ris = Pratica::DeletePratica($IdPratica);
                            if ($ris) {
                                http_response_code(200);
                                echo json_encode(["eliminato" => "eliminato con successo"]);
                            } else {
                                http_response_code(400);
                                echo json_encode(["eliminato" => "eliminazione fallita"]);
                            }
                        }
                    }
                }
            }
            if ($cleanUrl == "cancellazione") {
                if ($_SERVER['REQUEST_METHOD'] === "PUT") {
                    if ($dati["permission"] == 1) {
                        $input = json_decode(file_get_contents("php://input"), true);

                        if (!isset($input["IdPratica"]) || !isset($input["Codice"])) {
                            http_response_code(404);
                            echo json_encode(["error" => $input['IdPratica']]);
                            return;
                        } else {
                            $IdPratica = $input["IdPratica"];
                            $Codice = $input["Codice"];
                            $ris = Pratica::TerminazioneForzata($IdPratica, $Codice);
                            if ($ris == 200) {
                                http_response_code(200);
                                echo json_encode(["eliminato" => "eliminato con successo"]);
                            } else {
                                http_response_code(400);
                                echo json_encode(["eliminato" => "eliminazione fallita"]);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(["error" => $e->getMessage()]);
            return;
        }

    }
}