<?php
/**
 * Main class of module mjunitcalc
 * @author MAGES Michał Jendraszczyk
 * @copyright (c) 2023, MAGES Michał Jendraszczyk
 * @license http://mages.pl MAGES Michał Jendraszczyk
 */


class Mjunitcalc extends Module
{
    /**
     * Definicja hooka w edycji produktu dla zdefiniowania jednostki + przeliczenia jednostki 
     * Definicja crona który będzie odpowiednio przenosić te dane do właściwości produktów tj jednostka + promocja jednostki 
     */
    public function __construct()
    {
        $this->name = 'mjunitcalc';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'MAGES Michał Jendraszczyk';

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Kalkulator jednostek produktowych');
        $this->description = $this->l('Moduł umozliwia kalkulacje watości ceny produktu za jednostkę podstawową');

        $this->confirmUninstall = $this->l('Usuń moduł?');
    }

    public function install()
    {
       
       return  parent::install() && $this->registerHook("displayHomeBottomContent");
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

     

    public function cronGoogle()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $products = @Product::getProducts($lang['id_lang'], null, null, 'id_product', 'ASC');

            $getIdsProduct = explode(",", Configuration::get('mj_google_ignore_ids'));
            $default_carrier = new Carrier((int) Configuration::get('PS_CARRIER_DEFAULT'));

            $xml = new GoolgeSimpleXMLElement('<?xml version="1.0"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"/>');

            $produkty = $xml->addChild("channel");

            $produkty->addChild("data", date('d-m-Y H:i:s'));
            $produkty->addChild("title", Configuration::get('PS_SHOP_NAME'));  // set title
            if($lang['id_lang'] == '1') { 
                //$newUrl = str_replace(".de",".pl", __PS_BASE_URI__);
                $produkty->addChild("link", iconv("UTF-8", "UTF-8", str_replace(".de",".pl",Tools::getHttpHost(true)) ));
            } else {
                $produkty->addChild("link", iconv("UTF-8", "UTF-8", Tools::getHttpHost(true) . __PS_BASE_URI__));    
            }
            $produkty->addChild("description", iconv("UTF-8", "UTF-8", Configuration::get('PS_SHOP_DESC'))); //set opis

            foreach ($products as $key => $product) {
                if (in_array($product['id_category_default'], (array) unserialize(Configuration::get('mj_google_tree_filled')))) {
                    if ((float)$product['price'] >= (float) Configuration::get('mj_google_price')) {
                        if (((int)(Product::getRealQuantity($product['id_product'])) >= (int) Configuration::get('mj_google_qty')) || (Configuration::get('PS_ORDER_OUT_OF_STOCK') == '1')) {
                            if ((new Product($product['id_product']))->active == true) {
                                if (!in_array($product['id_product'], $getIdsProduct)) {
                                    $produkt = $produkty->addChild("item");
                                    $produkt->addChild("g:g:id", Configuration::get('mj_google_prefix').$product['id_product']);

                                    $produkt->addChildWithCData("g:g:title", $product['name'], "", "");

                                    // if (trim($product['description_short']) != '') {
                                    //     $produkt->addChildWithCData("g:g:description", ucwords(Tools::strtolower(iconv("UTF-8", "UTF-8", $product['description_short']))), "", "");
                                    // } else {
                                        $produkt->addChildWithCData("g:g:description", ucwords(Tools::strtolower(iconv("UTF-8", "UTF-8", $product['description']))), "", "");
                                    //}
                                    $produkt->addChild("g:g:condition", $product['condition']);

                                    // Ustawianie ilości
                                    if ((int) (Product::getRealQuantity($product['id_product'])) > 1) {
                                        $product['quantity'] = "in stock";
                                    } else {
                                        $product['quantity'] = "out of stock";
                                    }

                                    $image = Image::getCover($product['id_product']);
                                    $p = new Product($product['id_product'], false, $lang['id_lang']);
                                    $link = new Link;
                                    //                $imagePath = $link->getImageLink($p->link_rewrite, $image['id_image'], 'large_default');

                                    if($lang['id_lang'] == '1') { 
                                        $shop_id = 2;
                                    } else   {
                                        $shop_id = 1;
                                    }

                                    $produkt->addChild("g:g:link", $link->getProductLink($product['id_product'], null, null, null, $lang['id_lang'], $shop_id)); //."?controller=product&id_product="

                                    $getProductImgs = $p->getImages($lang['id_lang']);
                                    foreach ($getProductImgs as $key => $img) {
                                        if ($key < 10) {
                                            if ($key == 0) {
                                                $url_image = $link->getImageLink($p->link_rewrite, $img['id_image'], ImageType::getFormattedName('large'));
                                                if($lang['id_lang'] == '1') {
                                                    $newUrl = str_replace(".de",".pl",$url_image);
                                                } else {
                                                    $newUrl = $url_image; 
                                                }
                                                $produkt->addChild("g:g:image_link", (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on" ? 'https://' : 'http://') . $newUrl);
                                            } else {

                                                $url_image = $link->getImageLink($p->link_rewrite, $img['id_image'], ImageType::getFormattedName('large'));
                                                if($lang['id_lang'] == '1') {
                                                    $newUrl = str_replace(".de",".pl",$url_image);
                                                } else {
                                                    $newUrl = $url_image; 
                                                }

                                                $produkt->addChild("g:g:additional_image_link", (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on" ? 'https://' : 'http://') . $newUrl);
                                            }
                                        }
                                    }

                                    $produkt->addChild("g:g:availability", $product['quantity']);
                                    if (!empty($product['ean13'])) {
                                        $produkt->addChild("g:g:gtin", $product['ean13']);
                                    } else {
                                        $produkt->addChild("g:g:gtin", '');
                                    }
                                    $produkt->addChildWithCData("g:g:mpn", iconv("UTF-8", "UTF-8", $product['reference']));

                                    $produkt->addChild("g:g:brand", Configuration::get('PS_SHOP_NAME'));

                                    $produkt->addChild("g:g:adult", (Configuration::get('mj_google_adult') == 0) ? 'no' : 'yes');
                                        //Product::getPriceStatic($product['id_product'],true, null, 2, null, false, false, 1)

                                        // $id_product,
                                        // $usetax = true,
                                        // $id_product_attribute = null,
                                        // $decimals = 6,
                                        // $divisor = null,
                                        // $only_reduc = false,
                                        // $usereduc = true,
                                        // $quantity = 1,
                                        // $force_associated_tax = false,
                                        // $id_customer = null,
                                        // $id_cart = null,
                                        // $id_address = null,
                                        // &$specific_price_output = null,
                                        // $with_ecotax = true,
                                        // $use_group_reduction = true,
                                        // Context $context = null,
                                        // $use_customer_price = true,
                                        // $id_customization = null
                                    $produkt->addChild("g:g:price", number_format(Product::getPriceStatic($product['id_product'], true, null, 2, null, false, false, 1), 2, '.', ''). ' ' . ((new Currency(Configuration::get('PS_CURRENCY_DEFAULT')))->iso_code));

                                    if (@SpecificPrice::getSpecificPrice($product['id_product'], $this->context->shop->id, $this->context->currency->id, $this->context->country->id, Group::getCurrent()->id, 1, null, 0, 0, 0)['reduction'] > 0) {
                                        $produkt->addChild("g:g:sale_price", number_format(Product::getPriceStatic($product['id_product'], true, null, 2, null, false, true, 1), 2, '.', '') . ' ' . ((new Currency(Configuration::get('PS_CURRENCY_DEFAULT')))->iso_code));
                                    }

                                    if (Configuration::get('mj_google_size') != '0') {
                                        $produkt->addChild("g:g:size", FeatureValue::getFeatureValuesWithLang($lang['id_lang'], Configuration::get('mj_google_size'), true)[$key]['value']);
                                    }
                                    if (Configuration::get('mj_google_color') != '0') {
                                        $produkt->addChild("g:g:color", FeatureValue::getFeatureValuesWithLang($lang['id_lang'], Configuration::get('mj_google_color'), true));
                                    }
                                    if (Configuration::get('mj_google_material') != '0') {
                                        $produkt->addChild("g:g:material", FeatureValue::getFeatureValuesWithLang($lang['id_lang'], Configuration::get('mj_google_material'), true));
                                    }
                                    if (Configuration::get('mj_google_gender') != '0') {
                                        $produkt->addChild("g:g:gender", FeatureValue::getFeatureValuesWithLang($lang['id_lang'], Configuration::get('mj_google_gender'), true));
                                    }
                                    if (Configuration::get('mj_google_pattern') != '0') {
                                        $produkt->addChild("g:g:pattern", FeatureValue::getFeatureValuesWithLang($lang['id_lang'], Configuration::get('mj_google_pattern'), true));
                                    }

                                    $dostawa = $produkt->addChild("g:g:shipping");
                                    $dostawa->addChild("g:g:country", Tools::strtoupper($lang['iso_code']));

                                    //$dostawa->addChild("g:g:price", number_format(round($default_carrier->getDeliveryPriceByWeight($product['weight'], 1), 2), 2, '.', '') . ' ' . ((new Currency(Configuration::get('PS_CURRENCY_DEFAULT')))->iso_code));
                                    $dostawa->addChild("g:g:price", number_format(round((new Carrier(Configuration::get('mj_google_carrier')))->getDeliveryPriceByPrice(Product::getPriceStatic($product['id_product'], true, null, 2, null, false, true, 1), Configuration::get('mj_google_zone'))), 2, '.', '') . ' ' . ((new Currency(Configuration::get('PS_CURRENCY_DEFAULT')))->iso_code));
                                    $dostawa->addChild("g:g:service", "Standard");
                                }
                            }
                        }
                    }
                }
            }
            $xml->asXML(dirname(__FILE__) . "/mj-google_" . $lang['iso_code'] . ".xml");
        }
    }
}
