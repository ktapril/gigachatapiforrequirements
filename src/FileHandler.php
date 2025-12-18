<?php

namespace App;

use Smalot\PdfParser\Parser;

class FileHandler
{
    private $uploadDir = '../storage/uploads';

    public function __construct()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function extractText($filePath, $extension)
    {
        switch ($extension) {
            case 'txt':
                return file_get_contents($filePath);
            case 'docx':
                return $this->extractFromDocx($filePath);
            case 'pdf':
                return $this->extractFromPdf($filePath);
            default:
                throw new \Exception('неподдерживаемый формат файла');
        }
    }

    private function extractFromDocx($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === TRUE) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml) {
                $striped = $this->stripXml($xml);
                return $striped;
            } else {
                throw new \Exception('не удалось извлечь текст из .docx');
            }
        } else {
            throw new \Exception('не удалось открыть .docx файл');
        }
    }

    private function stripXml($xml)
    {
        $xml = \preg_replace('/<[^>]*>/', ' ', $xml);
        return \trim(\html_entity_decode($xml, \ENT_XML1, 'UTF-8'));
    }

    private function extractFromPdf($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }
}