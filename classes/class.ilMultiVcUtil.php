<?php

use ILIAS\DI\Container;

/**
 * MultiVc Util class
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 */
class ilMultiVUtil
{

    /** @var Container $dic */
    private $dic;

    /** @var ilObjMultiVc $multiVcObject */
    private $multiVcObject;

    /** @var ilMultiVcConfig $multiVcConfig */
    private $multiVcConfig;


    /**
     * @param string $component VC e. g. spreed | bbb
     * @return array
     */
    public function getObjConfigAvailSetting(string $component = ''): array
    {
        if( !(bool)$component )
        {
            return $this->objConfigAvailSetting;
        }

        return $this->objConfigAvailSetting[$component];

    }

    /**
     * @param string $search
     * @param string $component
     * @return bool
     */
    public function isObjConfig(string $search): bool
    {
        return true; // false !== array_search($search, $this->objConfigAvailSetting[$this->getShowContent()]);
    }


    /**
     * Constructor
     * @param int|null $connId
     * @param int|null $refId
     */
	public function __construct(?int $connId = null, ?int $refId = null)
	{
        $refId = $refId ?? 0;

	    global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        $this->multiVcConfig = new ilMultiVcConfig($connId);

        $this->multiVcObject = new ilObjMultiVc($refId);

	}

    /**
     * @param string $value
     * @return string
     */
    public static function removeUnsafeChars(string $value)
    {
        $remove = ["\n", "\r", "\t", '"', '\'', "<?", "?>"];
        $value = str_replace($remove, ' ', $value);
        foreach (["/<[^>]*>/", "%<\/[^>]*>]%", "%[\s]{2,}%"] as $regEx) {
            $value = preg_replace($regEx, ' ', $value);
        } // EOF foreach as $regEx)
        return trim($value);
    }

    /**
     * @param ilPropertyFormGUI $form
     * @param array $postVar
     * @param string[] $allowed
     * @return false
     */
    public static function checkUrl(ilPropertyFormGUI &$form, array $postVar, $allowed = ['https']) {
        foreach( $postVar as $name ) {
            /** @var  ilTextInputGUI $field */
            $field = $form->getItemByPostVar($name);
            if( (bool)($value = $field->getValue()) ) {
                foreach( $allowed as $check ) {
                    if( !(bool)substr_count($check, $value) ) {
                        return false;
                    }
                }
            }
        }
    }

}
