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

    public $arr_units = [
        'kilogram' => "kg",
        'litr' => 'l',
        'metr' => 'm',
        'metr kwadratowy' => 'm2'
    ];

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
     
        $createTable = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."mjunitcalc(id_product INT NOT NULL, mjunitcalc_volume FLOAT, mjunitcalc_base_unit VARCHAR(64), mjunitcalc_base_unit_short VARCHAR(16))";

        DB::getInstance()->Execute($createTable);

       return  parent::install() && $this->registerHook("displayAdminProductsPriceStepBottom")
       && $this->registerHook("actionProductUpdate") && $this->registerHook("displayProductPriceBlock") && $this->registerHook('displayProductListReviews') && $this->registerHook('displayProductAdditionalInfo') && $this->registerHook('ActionObjectProductUpdateAfter') && $this->registerHook('actionObjectUpdateAfter');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

     public function hookDisplayAdminProductsPriceStepBottom($params) { 

         $getParameters = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."mjunitcalc WHERE id_product = '".$params['id_product']."'");
         $getProduct = new Product($params['id_product']);
         if(count($getParameters)) { 
            $this->context->smarty->assign(
                array(
                    'mjunitcalc_volume' => $getParameters[0]['mjunitcalc_volume'],
                    'mjunitcalc_base_unit' => $getParameters[0]['mjunitcalc_base_unit'],
                    'id_product' => $params['id_product'],
                    'jednostki' => $this->arr_units,
                    'price' => $getProduct->price
                )
           );
         } else { 
            $this->context->smarty->assign(
                array(
                    'mjunitcalc_volume' => '',
                    'mjunitcalc_base_unit' => '',
                    'id_product' => $params['id_product'],
                    'jednostki' => $this->arr_units,
                    'price' => $getProduct->price
                )
           );
         }

       
        return $this->fetch('module:mjunitcalc/views/templates/hooks/admin_products_prices.tpl');
     }

     public function hookActionProductUpdate($params) { 

        $getProduct = $params['product'];

        $checkIfExist = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."mjunitcalc WHERE id_product = '".$getProduct->id."' LIMIT 1");

        if(count($checkIfExist) > 0) { 
            // Istnieje, aktualizuj 
            if(!empty(Tools::getValue('mjunitcalc_volume')) && !empty(Tools::getValue('mjunitcalc_base_unit'))) {
                Db::getInstance()->update('mjunitcalc', array(
                    'mjunitcalc_volume' => pSQL(Tools::getValue('mjunitcalc_volume')),
                    'mjunitcalc_base_unit' => pSQL(Tools::getValue('mjunitcalc_base_unit')),
                    'mjunitcalc_base_unit_short' => $this->getShortUnitFromUnit(pSQL(Tools::getValue('mjunitcalc_base_unit'))),
                ), 'id_product = '.$getProduct->id, 1, true);
            }
        } else { 
            // Nie istnieje dodaj
            Db::getInstance()->insert('mjunitcalc', array( 
                'id_product' => pSQL($getProduct->id),
                'mjunitcalc_volume' => pSQL(Tools::getValue('mjunitcalc_volume')),
                'mjunitcalc_base_unit' => pSQL(Tools::getValue('mjunitcalc_base_unit')),
                'mjunitcalc_base_unit_short' => $this->getShortUnitFromUnit(pSQL(Tools::getValue('mjunitcalc_base_unit')))
                ));    
        } 

        $getCurrentPropertiesUnits = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."mjunitcalc WHERE id_product = '".$getProduct->id."' LIMIT 1");

            
         /*   
        if(count($getCurrentPropertiesUnits) > 0) { 

            $unit_price = (float) $p->price / (float)$getCurrentPropertiesUnits[0]['mjunitcalc_volume'];
            $unity = (string) $getCurrentPropertiesUnits[0]['mjunitcalc_base_unit'];
            

            DB::getInstance()->Execute("UPDATE "._DB_PREFIX_."product SET unit_price = '$unit_price', unity = '$unity' WHERE id_product = '".$getProduct->id."'");
        }    

      */
     }
 

     public function getShortUnitFromUnit($unit)    {
        try {
            $short = $this->arr_units[$unit];
            return $short;
        } catch(\Exception $e) { 
            return 'j';
        }
     }

     public function  hookDisplayProductPriceBlock($params)
     { 
       // return "cena jednostkowa";  
       //return ' - ';
     }
     public function hookDisplayProductAdditionalInfo($params)  {
         $p = $params['product'];

         $getCurrentPropertiesUnits = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."mjunitcalc WHERE id_product = '".$p->id."' LIMIT 1");
     }
     public function hookDisplayProductListReviews($params) { 
        $p = $params['product'];
        return $p->unit_price_full;
     }
    public function cronUnit()
    {
        $getProducts = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."product");

        foreach($getProducts as $prod) { 
            $getCurrentPropertiesUnits = DB::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."mjunitcalc WHERE id_product = '".$prod['id_product']."' LIMIT 1");

            
            if(count($getCurrentPropertiesUnits) > 0) { 

                $p = new Product($prod['id_product']);
                $p->unit_price = (float) $p->price / (float)$getCurrentPropertiesUnits[0]['mjunitcalc_volume'];
                $p->unity = (string) $getCurrentPropertiesUnits[0]['mjunitcalc_base_unit'];
                $p->update();
            }            
        }


         
    }
}
