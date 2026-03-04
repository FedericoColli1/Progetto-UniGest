<?php
/**@class Controller
 * string $api server per scrivere l'url della richiesta ricevuta per smistarla poi al corretto gateway.
 * Inizialmente vuota, viene inserita la stringa tramite @see set_api($api)
 */
class Controller{
    private $api="";
    public function set_api($api){
        $this->api=$api;
    }
    /**
     * handle_request server per suddividere l'uri in piu parti
     * viene poi controllata la prima parte, se vuota restituisce errore, altrimenti viene gira la richiesta al gateway corretto per la sua gestione
     */
    public function handle_request(){
        $uri = preg_replace("/^" . preg_quote($this->api, "/") . "/", "", $_SERVER['REQUEST_URI']);
        $uri = preg_replace('/\\/$/', "", $uri);
        $parts = explode("/",$uri);
        
        if (empty($parts[1])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid API request"]);
            return;
        }

        switch ($parts[1]){
            case "users":
                $gateway = new UserGateway($parts);
                break;
            case "pratiche":
                $gateway = new PraticheGateway($parts);
                break;
            default:
                http_response_code(404);
                echo json_encode(["error" => "Endpoint not found"]);
                return;
        }
        try{
            $gateway->handle_request($parts);
        }
        catch (Exception $e){
            error_log("Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Internal Server Error"]);
        }
    }
}

?>