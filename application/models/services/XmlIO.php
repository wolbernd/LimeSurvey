<?php

namespace LimeSurvey\Models\Services;

/**
 * XML input/output.
 */
class XmlIO
{
    /**
     * @param SimpleXMLElement $xml
     * @param string $filename
     * @return mixed
     */
    public function save(\SimpleXMLElement $xml, $filename)
    {
        // If the filename isn't specified, this function returns a string on success and FALSE on error.
        // If the parameter is specified, it returns TRUE if the file was written successfully and FALSE otherwise.
        return $xml->saveXml($filename);
    }

    /**
     * @param string $path
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public function load($path)
    {
        $oldState = libxml_disable_entity_loader(true);
        $file = file_get_contents($path);
        if (empty($file)) {
            throw new \Exception(
                sprintf(
                    gT('Found no file at path %s'),
                    $path
                )
            );
        }
        $xml = simplexml_load_string($file);
        libxml_disable_entity_loader($oldState);
        return $xml;
    }
}
