<?php

/**
 *  Клас  'unit_MinkPPurchases' - PHP тестове за проверка на покупки различни варианти, вкл. некоректни данни
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPPurchases extends core_Manager {
    //Изпълнява се след unit_MinkPbgERP!
    //http://localhost/unit_MinkPPurchases/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        $res = '';
        $res .= "<br>".'MinkPPurchases';
        $res .=  " 1.".$this->act_PurchaseQuantityMinus();
        $res .=  " 2.".$this->act_PurchaseQuantityZero();
        //$res .= "  3.".$this->act_PurchasePriceMinus();
        $res .= "  4.".$this->act_PurchaseDiscountMinus();
        $res .= "  5.".$this->act_PurchaseDiscount101();
        $res .= "  6.".$this->act_CreatePurchaseVatInclude();
        $res .= "  7.".$this->act_CreatePurchaseEURVatFree();
        $res .= "  8.".$this->act_CreatePurchaseEURVatFreeAdv();
        $res .= "  9.".$this->act_CreateCreditDebitInvoice();
        $res .= "  10.".$this->act_CreateCreditDebitInvoiceVATFree();
        $res .= "  11.".$this->act_CreateCreditDebitInvoiceVATNo();
        $res .= "  12.".$this->act_CreatePurchaseAdvPaymentInclVAT();
        $res .= "  13.".$this->act_CreatePurchaseAdvPaymentSep();
        $res .= "  14.".$this->act_CreatePurchaseDifVAT();
        $res .= "  14.".$this->act_CreatePurchaseExpense();
        return $res;
    }
       
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
    
    /**
     * Избор на фирма
     */
    public function SetFirm()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    
    /**
     * Избор на чуждестранна фирма
     */
    public function SetFirmEUR()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('N');
        $Company = 'NEW INTERNATIONAL GMBH';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    
    /**
     * Проверка за отрицателно количество
     */
    //http://localhost/unit_MinkPPurchases/PurchaseQuantityMinus/
    function act_PurchaseQuantityMinus()
    {
      
        // Логваме се
        $browser = $this->SetUp();
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
       
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
    
        $browser->setValue('note', 'MinkPPurchaseQuantityMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на Покупката
        $browser->press('Чернова');
        
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '-2');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателно количество', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка "Не е над - \'0,0000\'"', 'warning');
        }
        //return $browser->getHtml();
    
    }
    /**
     * Проверка за нулево количество
     */
    //http://localhost/unit_MinkPPurchases/PurchaseQuantityZero/
    function act_PurchaseQuantityZero()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
    
        $browser->setValue('note', 'MinkPPurchaseQuantityZero');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на Покупката
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при нулево количество', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка "Не е над - \'0,0000\'"', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Проверка за отрицателна цена (още няма контрол при въвеждането)
     */
    //http://localhost/unit_MinkPPurchases/PurchasePriceMinus/
    function act_PurchasePriceMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
    
        $browser->setValue('note', 'MinkPPurchasePriceMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на Покупката
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '-3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Цена\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателна цена', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка "Не е над - \'0,0000\'"', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Проверка за отрицателна отстъпка
     */
    //http://localhost/unit_MinkPPurchases/PurchaseDiscountMinus/
    function act_PurchaseDiscountMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
       
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
        
        $browser->setValue('note', 'MinkPPurchaseDiscountMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на Покупката
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', -3);
        // Записваме артикула
        $browser->press('Запис');
       
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателна отстъпка', 'warning');
        }
         
        //if(strpos($browser->gettext(), 'Не е над - \'0,00 %\'')) {//не го разпознава
        //} else {
        //    return unit_MinkPbgERP::reportErr('Не дава грешка "Не е над - \'0,0000\'"', 'warning');
        //}
        //return $browser->getHtml();
    }
    
    /**
     * Проверка за отстъпка, по-голяма от 100%
     */
    //http://localhost/unit_MinkPPurchases/PurchaseDiscount101/
    function act_PurchaseDiscount101()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
    
        $browser->setValue('note', 'MinkPPurchaseDiscount101');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на Покупката
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', '101,55');
        // Записваме артикула
        $browser->press('Запис');
         
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отстъпка над 100%', 'warning'); 
        }
        
        //if(strpos($browser->gettext(), 'Над допустимото - \'100,00 %\'')) {//не го разпознава
        //} else {
        //    return unit_MinkPbgERP::reportErr('Не дава грешка при отстъпка над 100%', 'warning');
        //}
        //return $browser->getHtml();
    
    } 
    
    /**
     * Покупка - включено ДДС в цените
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseVatInclude/
    function act_CreatePurchaseVatInclude()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseVatInclude');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 10);
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 10);
        // Записване на артикула
        $browser->press('Запис');
        
        // активиране на Покупката
        $browser->press('Активиране');
        //return $browser->getHtml();
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 3,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Тридесет и три BGN и 0,19')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Когато няма автом. избиране
        // Складова разписка
        // протокол
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 20%: BGN 27,66')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
        //return $browser->getHtml();
    }
       
    /**
    * Покупка EUR - освободена от ДДС
    */
         
    //http://localhost/unit_MinkPPurchases/CreatePurchaseEURVatFree/
    function act_CreatePurchaseEURVatFree()
    {
        // Логване
        $browser = $this->SetUp();
        
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
        
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
        $browser->setValue('note', 'MinkPPurchaseEURVatFree');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', 'exempt');
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");//Ако контрагентът е от България дава грешка 234 - NodeElement.php
        // Записване черновата на Покупката
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 10);
        
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 10);
        // Записване на артикула
        $browser->press('Запис');
         
        // активиране на Покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '3,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Thirty-three EUR and 0,19')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }    
        
        // Складова разписка
        // Когато няма автом. избиране  
        // протокол
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->setValue('number', '101');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 0%: BGN 64,91')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Покупка EUR - освободена от ДДС, авансово пл.
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseEURVatFreeAdv/
    function act_CreatePurchaseEURVatFreeAdv()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
        
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
        $browser->setValue('note', 'MinkPPurchaseVatFreeAdv');
        $browser->setValue('paymentMethodId', "100% авансово");
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");//Ако контрагентът е от България дава грешка 234 - NodeElement.php
        $browser->setValue('chargeVat', 'exempt');
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '3');
        $browser->setValue('packPrice', '1,123');
        $browser->setValue('discount', 2);
    
        $browser->press('Запис');
         
        // активиране на Покупката
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 0,07')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Three EUR and 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Складова разписка с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Словом: Три EUR и 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в складова разписка', 'warning');
        }
        
        // Фактура
        $browser->press('Вх. фактура');
        //$browser->setValue('amountAccrued', '3,3');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->setValue('number', '102');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 0%: BGN 6,45')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
    
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Покупка - Кредитно и дебитно известие
     */
     
    //http://localhost/unit_MinkPPurchases/CreateCreditDebitInvoice/
    function act_CreateCreditDebitInvoice()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseCIDI');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
        
        // активиране на Покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Деветдесет и три BGN и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Когато няма автом. избиране
        // Складова разписка
        // протокол
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '2');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('number', '3');
        $browser->setValue('changeAmount', '-22.36');
        //return $browser->getHtml();
        $browser->press('Чернова');
       
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус двадесет и шест BGN и 0,83')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '4');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и шест BGN и 0,80 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
        
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '5');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '1.3');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), ' Минус тридесет и един BGN и 0,20 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
        
        // Дебитно известие - сума
        $browser->press('Известие');
        $browser->setValue('number', '6');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и шест BGN и 0,64 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '7');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), ' Двадесет и три BGN и 0,40 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '8');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '2.3');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), ' Шестнадесет BGN и 0,80 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
        //return $browser->getHtml();
    } 
    
    /**
     * Покупка - Кредитно и дебитно известие - освободено от ДДС (валута)
     */ 
     
    //http://localhost/unit_MinkPPurchases/CreateCreditDebitInvoiceVATFree/
    function act_CreateCreditDebitInvoiceVATFree()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseCIDICVATFree');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");
        $browser->setValue('chargeVat', 'exempt');
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на Покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Ninety-three EUR and 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Складова разписка
        // Когато няма автом. избиране 
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->setValue('number', '103');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие - сума 
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-22.36');
        $browser->setValue('number', '104');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус двадесет и два EUR и 0,36')) {
            
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
    
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '105');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и шест EUR и 0,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
    
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '106');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '1.3');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и един EUR и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
    
        // Дебитно известие - сума 
        $browser->press('Известие');
        $browser->setValue('number', '107');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и два EUR и 0,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '108');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и три EUR и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
        
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '109');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '2.4');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Два EUR и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
    
        //return $browser->getHtml();
    }
    /**
     * Покупка - Кредитно и дебитно известие без ДДС (валута)
     */
     
    //http://localhost/unit_MinkPPurchases/CreateCreditDebitInvoiceVATNo/
    function act_CreateCreditDebitInvoiceVATNo()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseCIDICVATNo');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        //$browser->setValue('chargeVat', "Без начисляване на ДДС");
        $browser->setValue('chargeVat', 'no');
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на Покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Ninety-three EUR and 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Складова разписка
        // Когато няма автом. избиране 
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '110');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('number', '111');
        $browser->setValue('changeAmount', '-22.36');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус двадесет и два EUR и 0,36')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
    
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '112');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и шест EUR и 0,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
    
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '113');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '1.3');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и един EUR и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
    
        // Дебитно известие - сума
        $browser->press('Известие');
        $browser->setValue('number', '114');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и два EUR и 0,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
    
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '115');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и три EUR и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
    
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '116');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '2.4');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Два EUR и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
    
        //return $browser->getHtml();
    }
    /**
     * Покупка - схема с авансово плащане, Включено ДДС в цените
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseAdvPaymentInclVAT/
    function act_CreatePurchaseAdvPaymentInclVAT()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPAdvancePaymentInclVAT');
        $browser->setValue('paymentMethodId', "20% авансово и 80% преди експедиция");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
         
        // Записваме черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '023');
        $browser->setValue('packPrice', '09/013*02');
        $browser->setValue('discount', 3);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 11);
        $browser->setValue('packPrice', 1.11);
        $browser->setValue('discount', 1);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '160 / 05-03*08');//8
        $browser->setValue('packPrice', '10/05+3*08');//26
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме Покупката
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 49,78')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Двеста четиридесет и осем BGN и 0,91')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '49,78');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '9');
        $browser->setValue('amountAccrued', '49,78');
        $browser->press('Чернова');
        //$browser->setValue('paymentType', 'По банков път');
        $browser->press('Контиране');
    
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '199,13');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Складова разписка с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Тридесет BGN и 0,89')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в складова разписка', 'warning');
        }
         
        // протокол
        $browser->press('Приемане');
        $browser->setValue('template', 'Приемателен протокол за услуги с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двеста и осемнадесет BGN и 0,02')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в протокол за услуги', 'warning');
        }
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '10');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-41,48')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
         
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Проверка на статистиката
        if(strpos($browser->gettext(), '248,91 248,91 248,91 248,91')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
     
    /**
     * Покупка - схема с авансово плащане, отделно ДДС
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseAdvPaymentSep/
    function act_CreatePurchaseAdvPaymentSep()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPAdvancePayment');
        $browser->setValue('paymentMethodId', "20% авансово и 80% преди експедиция");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '10');
        
        // Записваме артикула
        $browser->press('Запис');
        // активираме Покупката
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 240,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Хиляда и двеста BGN')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '240');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '11');
        $browser->press('Чернова');
        //return 'paymentType';
        //$browser->setValue('paymentType', 'По банков път');
        $browser->press('Контиране');

        if(strpos($browser->gettext(), 'Двеста и четиридесет BGN')) {
        } else {
            return "Грешна сума във фактурата за аванс";
        }
       
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '960.00');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Складова разписка с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
       
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '12');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-200,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
         
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Покупка на артикули с различно ДДС, (вкл. КИ и ДИ)
     */
    //http://localhost/unit_MinkPPurchases/CreatePurchaseDifVAT/
    function act_CreatePurchaseDifVAT()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseDifVAT');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяме нов артикул - 20% ДДС
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '20');
        $browser->setValue('packPrice', '20');
        // Записване артикула и добавяне нов - 9% ДДС
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Артикул ДДС 9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '9');
        $browser->setValue('packPrice', '9');
        // Записваме артикула
        $browser->press('Запис');
    
        // активиране на Покупката
        $browser->press('Активиране');
        //return $browser->getHtml();
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 80,00')) {
        } else {
            return "Грешно ДДС 20%";
        }
        if(strpos($browser->gettext(), 'ДДС 9%: BGN 7,29')) {
        } else {
            return "Грешно ДДС 9%";
        }
        if(strpos($browser->gettext(), 'Петстотин шестдесет и осем BGN и 0,29')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // Складова разписка
        // Когато няма автом. избиране
               
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '13');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 20%: BGN 400,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа 20%', 'warning');
        }
        if(strpos($browser->gettext(), 'Данъчна основа 9%: BGN 81,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа 9%', 'warning');
        }
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '14');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
       
        $browser->setValue('quantity', '18');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), ' Минус четиридесет и осем BGN')) {
        } else {
            return "Грешна сума в КИ - количество";
        }
        
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '15');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '15');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус сто и двадесет BGN')) {
        } else {
            return "Грешна сума в КИ - цена";
        }
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->setValue('number', '16');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('quantity', '21');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), ' Двадесет и четири BGN')) {
        } else {
            return "Грешна сума в ДИ - количество";
        }
        
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->setValue('number', '17');
        $browser->press('Чернова');
        $browser->click('Редактиране на артикул');
        $browser->setValue('packPrice', '20,14');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Словом: Три BGN и 0,36 ')) {
        } else {
            return "Грешна сума в ДИ - цена";
        }
        //return $browser->getHtml();
    }
    
    /**
     * Покупка 1 - разходен обект, покупка 2 - разходи
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseExpense/
    function act_CreatePurchaseExpense()
    {
    
        // Логване
        $browser = $this->SetUp();
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма доставчик';
        $browser->click($Company);
        $browser->press('Папка');
        
        //Отваряне папката на фирмата
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        $browser->setValue('note', 'MinkPPurchaseExpense');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        // Записване артикула и добавяне нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на Покупката
        $browser->press('Активиране');
        //return $browser->getHtml();
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Четиридесет и четири BGN и 0,25')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Когато няма автом. избиране
        // Складова разписка
        // протокол
        $browser->press('Разходен обект');
        
        //ID на перото
        $browser->click('Настройки');
        $browser->click('Пера');
        $browser->setValue('listId', 'Сделки');
        $browser->setValue('search', 'Доставчик');
        $browser->press('Филтрирай');
        $browser->click('Информация за перото');
        //echo $browser->getHtml();
        $purId = "59";
        ///////$purid - да се зареди ID 
        $purId = $purId .'.17';
        
        //Покупка 2 - услуги
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
        
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        $browser->setValue('note', 'MinkPPurchaseService');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', 'Договор за покупка на услуга');
        // Записване черновата на Покупката
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1');
        $browser->setValue('packPrice', '100');
        
        $browser->setValue('expenseItemId', $purId);
        //грешка!
        //$browser->setValue('allocationBy', 'По стойност');
        //$browser->refresh('Запис');
        // Записване на артикула
        $browser->press('Запис');
        
        // активиране на Покупката
        $browser->press('Активиране');
        //$browser->setValue('action_pay', False);
        $browser->setValue('action_ship', 'ship');
        $browser->press('Активиране/Контиране');
        ///Проверка в разходния обект и фактуриране...//////////
        $browser->click('16 pur');
        //return $browser->getHtml();
        // Фактура в покупка 1
        $browser->press('Вх. фактура');
        $browser->setValue('number', '5418');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 20%: BGN 36,88')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
        //Проверка на статистиката
        if(strpos($browser->gettext(), '44,25 44,25 44,25 44,25')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        if(strpos($browser->gettext(), '0,00 0,00 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * Покупка - включено ДДС в цените - извънредни разходи
     */
     
    //http://localhost/unit_MinkPPurchases/CreatePurchaseExtraExpenses/
    function act_CreatePurchaseExtraExpenses()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        // нова Покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->hasText('Създаване на Покупка');
        $browser->setValue('note', 'MinkPPurchaseExtraIncome');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на Покупката
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '30');
        $browser->setValue('packPrice', '1,312');
        $browser->setValue('discount', 10);
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на Покупката
        $browser->press('Активиране');
        //return $browser->getHtml();
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 3,94')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Тридесет и пет BGN и 0,42')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Когато няма автом. избиране
        // Складова разписка
        // протокол
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '1128');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 20%: BGN 29,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
        
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '49,78');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '35,42 35,42 49,78 35,42')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера');
        }
        if(strpos($browser->gettext(), 'BGN 0,00 BGN 14,36')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума - извънреден разход');
        }
        //return $browser->getHtml();
    }
}