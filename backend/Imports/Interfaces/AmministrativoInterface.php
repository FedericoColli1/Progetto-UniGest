<?php
require_once __DIR__ . "/UserInterface.php";

interface AmministrativoInterface extends UserInterface {
    public function getIdUnita();

    public function getPraticheAmministrativo();

    public static function getIdFromMail($mail);
}