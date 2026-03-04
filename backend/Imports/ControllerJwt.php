<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
/**
 * @class ControllerJwt
 * Classe astratta che gestisce la creazione e il controllo dei JSON Web Token (JWT).
 * Questa classe è responsabile dell'autenticazione degli utenti e della verifica dei token.
 * Le funzioni che può svolgere sono:
 * - Controllare la validità di un JWT ricevuto nell'header di autorizzazione.
 * - Creare un nuovo JWT per un utente autenticato.
 */
abstract class ControllerJwt
{

    /**
     * Controlla la validità di un JSON Web Token (JWT) presente nell'header 'Authorization' della richiesta HTTP.
     * Estrae il token, lo decodifica utilizzando una chiave segreta e restituisce i dati del payload.
     * In caso di token mancante o non valido, restituisce un errore HTTP 401.
     *
     * @param array $headers Tutti gli header della richiesta HTTP.
     * @param string $prova Il valore dell'header 'Authorization' (per debug).
     * @param string $authHeader Il valore dell'header 'Authorization'.
     * @param string $tokenType Il tipo di token (es. "Bearer").
     * @param string $token Il JWT effettivo.
     * @param object $dati I dati decodificati dal payload del JWT.
     * @return array|null I dati del payload del JWT decodificato in formato array associativo, o null se il token è mancante o non valido.
     */
    public static function control()
    {
        $headers = getallheaders();
        $prova=$headers['Authorization'];
        if (isset($prova)) {
            $authHeader = $headers['Authorization'];
            error_log($authHeader = $headers['Authorization']);
            list($tokenType, $token) = explode(" ", $authHeader, 2);
            error_log($tokenType);
            if ($tokenType === "Bearer") {
                $dati=JWT::decode($token, new Key("Keyz",'HS256'));
                $dati = json_decode(json_encode($dati), true);
            }
            return $dati;
        }
        else {
            http_response_code(401);
            echo json_encode(["error" => "Autorizzazione mancante"]);
            return;
        }

    }
    /**
     * Crea un nuovo JSON Web Token (JWT) per un utente dopo l'autenticazione.
     * Verifica le credenziali dell'utente (email e password) nel database e,
     * se valide, genera un token con informazioni sul ruolo e i permessi dell'utente.
     *
     * @param string $email L'indirizzo email dell'utente.
     * @param string $pwd La password dell'utente.
     * @param string $secret_key La chiave segreta utilizzata per firmare il JWT.
     * @param Database $dbconn Oggetto Database per la connessione al DB.
     * @param string $query Query SQL per recuperare i dati dell'utente.
     * @param array $results Il set di risultati della query.
     * @param array $row L'array associativo contenente i dati dell'utente dal database.
     * @param array $payload Il payload del JWT, contenente informazioni sull'utente.
     * @param string $jwt Il JSON Web Token generato.
     * @param array $user Array contenente il token da restituire.
     * @param array $errore Array di errore in caso di credenziali non valide.
     * @return void Restituisce il token JWT in formato JSON o un messaggio di errore HTTP.
     */
    public static function CreateToken($email,$pwd,$secret_key){
        $dbconn = new Database();

        $query = "SELECT Utente.Id, Utente.Nome, Utente.Pwd, Utente.Jolly, Amministrativo.IdAmministrativo, Amministrativo.Direttore
                                FROM Utente LEFT JOIN Amministrativo ON Amministrativo.IdAmministrativo = Utente.Id 
                                WHERE Utente.Mail = ?";
        $results = $dbconn->query($query,[$email]);
        $row = $results->fetch_assoc();
        if($row && password_verify($pwd,$row['Pwd'])) {
            if(!is_null($row['IdAmministrativo'])) {
                if($row['Direttore']==1){
                    $payload = [
                        "sub" => $row['Id'],
                        "iat" => time(),
                        "exp" => time() + 28800,
                        "role" => "direttore",
                        "permission" => $row['Jolly'],
                        "iss" => $row["Nome"]
                    ];
                }
                else {
                    $payload = [
                        "sub" => $row['Id'],
                        "iat" => time(),
                        "exp" => time() + 28800,
                        "role" => "amministrativo",
                        "permission" => $row['Jolly'],
                        "iss" => $row["Nome"]
                    ];
                }

                $jwt = JWT::encode($payload, $secret_key, 'HS256');
                header("Content-Type: application/json");
                $user = [
                    "Token" => $jwt
                ];

                http_response_code(200);
                echo json_encode($user);
            }

            else{

                $payload = [
                    "sub" => $row['Id'],
                    "iat" => time(),
                    "exp" => time() + 28800,
                    "role" => "base",
                    "iss" => $row["Nome"],
                    "permission" => $row['Jolly']
                ];

                $jwt = JWT::encode($payload, $secret_key, 'HS256');
                header("Content-Type: application/json");
                $user = [
                    "Token" => $jwt
                ];

                http_response_code(200);
                echo json_encode($user);

            }
        
        }
        else {
            
            $errore = [
                "row" => "0"
            ];
            http_response_code(404);
            echo json_encode($errore);
        }

        $dbconn->close();
    }
}