<?php
require_once __DIR__ . "/AmministrativoInterface.php";

interface DirettoreInterface extends AmministrativoInterface{
    public function getDirettore();
    public function getPraticheDirettore();
    public static function Assegnazione($IdPassaggio, $IdAmministrativo);
    public function getAmministrativi();
    public function getAmministrativiAssegnati($IdPratica, $NPassaggio);
}