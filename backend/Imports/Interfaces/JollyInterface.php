<?php

require_once __DIR__ . "/UserInterface.php";

interface JollyInterface extends UserInterface {

    public function DeletePratica($IdPratica);

    public function TerminazionePratica($IdPratica,$Codice);

    public function TerminazionePassaggio($Npassaggio,$IdPratica, $IdPassaggio);

    public function getPraticheJolly();

    public function getAmministrativi($IdPratica, $NPassaggio);

    public function getAmministrativiAssegnati($IdPratica, $NPassaggio);
}