<?php

interface PassaggioInterface {

    public function getIdPassaggio();

    public function getNPassaggio();

    public function getIdPratica();

    public function getIdUnita();

    public function getListaDocRichiesti();

    public function getListaDocUscita();

    public static function Terminazione($Npassaggio,$IdPratica,$IdPassaggio);

    public static function UpdatePassaggio($IdPassaggio,$IdPratica);

    public static function GetNumPassaggio($pratica,$passaggio);

    public static function GetIdPassaggioFromNum($IdPratica,$NPassaggio);

    public static function CaricaDocumenti($IdPratica,$NPassaggio);

    public static function GetListDocRichiesti($IdPratica,$NPassaggio);

    public static function ListDocUscita($IdPratica,$NPassaggio);

    public static function SendDocument($IdPratica,$NPassaggio);

    public static function SalvaDoc($filename,$IdPratica,$IdPassaggio);
}