<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Clinicas\Utils;

use App\Services\BaseService;
use Illuminate\Http\UploadedFile;

/**
 * Description of Activities
 *
 * @author ander
 */
class UploadService extends BaseService {

    function resizeImage($imgOrigem, $imgDestino, $width, $height) {
        //REdmensionando imagem
        list( $imgLarg, $imgAlt, $imgTipo ) = getimagesize($imgOrigem);



        if ($imgLarg > $width || $imgAlt > $height) {
            // verifica se a largura é maior que a altura
            if ($imgLarg > $imgAlt) {
                $novaLargura = $width;
                $novaAltura = round(($novaLargura / $imgLarg) * $imgAlt);
            }
            // se a altura for maior que a largura
            elseif ($imgAlt > $imgLarg) {
                $novaAltura = $height;
                $novaLargura = round(($novaAltura / $imgAlt) * $imgLarg);
            }
            // altura == largura
            else {
                $novaAltura = $novaLargura = max(array($width, $height));
            }
        } else {
            $novaAltura = $imgAlt;
            $novaLargura = $imgLarg;
        }


        $novaImagem = imagecreatetruecolor($novaLargura, $novaAltura);

        switch ($imgTipo) {
            case 2:
                $srcImg = imagecreatefromjpeg($imgOrigem);


                imagecopyresampled($novaImagem, $srcImg, 0, 0, 0, 0, $novaLargura, $novaAltura, $imgLarg, $imgAlt);
                imagejpeg($novaImagem, $imgDestino);

                break;

            case 3 : $srcImg = imagecreatefrompng($imgOrigem);
                imagesavealpha($novaImagem, true);
                $cor_fundo = imagecolorallocatealpha($novaImagem, 0, 0, 0, 127);
                imagefill($novaImagem, 0, 0, $cor_fundo);
                imagecopyresampled($novaImagem, $srcImg, 0, 0, 0, 0, $novaLargura, $novaAltura, $imgLarg, $imgAlt);
                imagecolorallocate($novaImagem, 255, 0, 0);
                imagepng($novaImagem, $imgDestino);
                break;
            case 1: $srcImg = imagecreatefromgif($imgOrigem);
           
                imagecopyresampled($novaImagem, $srcImg, 0, 0, 0, 0, $novaLargura, $novaAltura, $imgLarg, $imgAlt);
                imagegif($novaImagem, $imgDestino);
                break;
            case 6: $srcImg = imagecreatefromwbmp($imgOrigem);
                imagecopyresampled($novaImagem, $srcImg, 0, 0, 0, 0, $novaLargura, $novaAltura, $imgLarg, $imgAlt);
                imagewbmp($novaImagem, $imgDestino);
                break;
        }
        imagedestroy($novaImagem);
        imagedestroy($srcImg);
    }

    public function uploadArquivos($nomeDominio, UploadedFile $file, $tipoArquivo) {

        $extensao = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        $nameFile = md5(uniqid(time())) . "." . $extensao;
        $baseDir = '../../app/perfis/' . $nomeDominio;
        $urlThumb = null;
        $resize = false;

        switch ($tipoArquivo) {
            case 'arquivo':
                $baseDir .= '/arquivos';
                $url = env('APP_URL_CLINICAS') . $nomeDominio . '/arquivos/';

                break;
            case 'foto':
                $baseDir .= '/fotos';
                $dirThumb = "/fotos_paciente_thumbs/" . $nameFile;
                $url = env('APP_URL_CLINICAS') . $nomeDominio . '/fotos/';
                $urlThumb = env('APP_URL_CLINICAS') . $nomeDominio . '/fotos/fotos_paciente_thumbs/';
                $resize = true;
                break;
        }


        $moveFile = $file->move($baseDir, $nameFile);
        if ($moveFile) {

            if ($resize) {
                $this->resizeImage($moveFile->getRealPath(), $baseDir . $dirThumb, 300, 200);
            }

            $retorno = [
                'originalName' => $originalName,
                'fileName' => $nameFile,
                'url' => $url . rawurldecode($nameFile),
            ];

            if (!empty($urlThumb)) {
                $retorno['urlThumb'] = $urlThumb . rawurldecode($nameFile);
            } else {
                $retorno['urlThumb'] = null;
            }

            return $retorno;
        } else {
            return false;
        }
    }

}
