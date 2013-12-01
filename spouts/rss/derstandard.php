<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news from derstandard.at with the full text
 * based on heise.php
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Robelix <roland@robelix.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class derstandard extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'News: Der Standard';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches the news from derstandard.at with full content (not only the header as content)';
    
    
    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     * 
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = array(
        "section" => array(
            "title"      => "Section",
            "type"       => "select",
            "values"     => array(
                "seite1" => "Seite 1",
            ),
            "default"    => "seite1",
            "required"   => true,
            "validation" => array()
        )
    );


    /**
     * addresses of feeds for the sections
     */
    private $feedUrls = array(
        "seite1"   => "http://derstandard.at/?page=rss&ressort=seite1",
    );


    /**
     * delimiters of the article text
     *
     * elements: start tag, attribute of start tag, value of start tag attribute, end
     */
    private $textDivs = array(
        array("div", "id", "artikelLeft", array('<ul class="lookupLinksArtikel"', '<div id="articleTools"', '<div id="weiterLesen")') ),
    );


    /**
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        parent::load(array( 'url' => $this->getXmlUrl($params)) );
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return $this->feedUrls[$params['section']];
    }


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $originalContent = file_get_contents($this->getLink());
            foreach($this->textDivs as $div) {
                $content = $this->getTag($div[1], $div[2], $originalContent, $div[0], $div[3]);
                if(is_array($content) && count($content)>=1) {
                    $content = $content[0];
                    
                    // remove empty <ul> - this happens if there is no image in article
                    $content = preg_replace('#<ul>\s*<li>\s*</li>\s*</ul>#ims', '', $content);
                    
                    return $content;
                }
            }
        }
        return parent::getContent();
    }
    
    
    /**
     * get tag by attribute
     * taken from http://www.catswhocode.com/blog/15-php-regular-expressions-for-web-developers
     *
     * @return string content
     * @return string $attr attribute
     * @return string $value necessary value
     * @return string $xml data string
     * @return string $tag optional tag
     */
    private function getTag($attr, $value, $xml, $tag=null, $end=null) {
        if(is_null($tag))
            $tag = '\w+';
        else
            $tag = preg_quote($tag);

        if(is_null($end))
            $end = '</\1>';
        elseif(is_array($end)) {
            $endparts = Array();
            foreach($end as $e) {
                $endparts[] = preg_quote($e);
            }
            $end = '('.implode('|', $endparts).')';
        } else
            $end = preg_quote($end);

        $attr = preg_quote($attr);
        $value = preg_quote($value);
        $tag_regex = '#<('.$tag.')[^>]*'.$attr.'\s*=\s*([\'"])'.$value.'\2[^>]*>(.*?)'.$end.'#ims';
        preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);
        return $matches[3];
    }
    
    
    /**
     * convert relative url to absolute
     *
     * @return string absolute url
     * @return string $relative url
     * @return string $absolute url
     */
    public static function absolute($relative, $absolute) {
        if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative)) {
            echo $relative;
            return $relative;
        }
        echo $absolute.$relative;
        return $absolute . $relative;
    }
}
