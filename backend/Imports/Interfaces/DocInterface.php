<?php

interface DocInterface {
    public static function CheckFile( $NPassaggio, $IdPratica, $NPassaggi);

    public static function AddDocument($IdPratica, $NPassaggio, $IdPassaggio);

    public function CreateDirectory($IdPratica, $Npassaggio, &$attiva);

    public static function DeleteDirectory($IdPratica, $Npassaggio = []);

    public static function DeleteFile($dir);

    public static function SearchDocumentPassaggio($IdPratica,$NPassaggio);
}