<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: *");
/**
 * require necessari per implementare JWT e la classe Database che si occupa della connessione al db mantenuto sul servizio mysql
 */
require_once 'jwt/vendor/autoload.php';
require_once 'Imports/Database.php';
require_once 'Imports/Class/Amministrativo.php';
require_once 'Imports/Class/User.php';
require_once 'Imports/ControllerJwt.php';

use Firebase\JWT\JWT;

class UserGateway extends Gateway
{

    private $dbconn;
    /**
     * chiave privata per la codifica del token in JWT
     */
    private $secret_key = "Keyz";

    public function handle_request($parts) {

        //creo la connessione con il database tramite il costruttore di Database
        $dbconn = new Database();

        //siccome nel frontend inviamo una richiesta in POST o OPTIONS verifichiamo la condizione del tipo di richiesta, se la richiesta non e' gestita inviamo error 504
        
        if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
            http_response_code(204);
            exit();
        }
        elseif ($_SERVER['REQUEST_METHOD'] === "POST") {
            //se si tratta di un post proveniente dalla scheda di login (nel frontend si invia la richiesta a ) 
            if($parts[2]=='login') {
                
                $input = json_decode(file_get_contents("php://input"),true);

                if(!isset($input['email']) || !isset($input['password'])) {

                    http_response_code(404);
                    echo json_encode(["error" => "Missing email or password"]);
                    return;

                }

                $email=urldecode($input['email']);
                $pwd=$input['password'];

                try{
                    ControllerJwt::CreateToken($email,$pwd, $this->secret_key);
                }
                catch (Exception $e) {

                    var_dump($e);
                    http_response_code(404);
                    $dbconn->close();
                    return;

                }
            }
            
        }
        else {

            http_response_code(501);
            $dbconn->close();
            return;

        }
    }
}
?>