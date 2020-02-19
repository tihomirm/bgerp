var dialog;
var activeInput;
var timeout;

function posActions() {

	activeInput = false;
	$(document.body).on('input', "input[name=ean]", function(e){
		var userText = $(this).val();
		$("#suggestions").find("option").each(function() {
		      if ($(this).val() == userText) {
		    	  $value = $(this).attr("data-value");
		    	  $("input[name=ean]").val($value);
		      }
		})
	});

	$('.large-field.select-input-pos').focus();

	// Забраняване на скалирането, за да избегнем забавяне
	if(isTouchDevice()){
		 $('meta[name=viewport]').remove();
		 $('meta').attr('name', 'viewport').attr('content', 'width=device-width, user-scalable=no').appendTo('head');
	}
	
	// Извикване на функцията за преизчисления на размерите на елементите
	if($('body').hasClass('wide')){
		calculateWidth();
		$(window).resize( function() {
			calculateWidth();
		});
	} 

	// Използване на числата за въвеждане в пулта
	$(document.body).on('click', ".numPad", function(e){
		var val = $(this).val();

		var inputElement = $('.select-input-pos');

		inputChars(inputElement, val);
	});

	
	// Добавяне на
	$(document.body).on('click', "#result-holder .receiptRow", function(e){
		//addResultByDataUrl(this);
	});
	
	// Добавяне на партида
	$(document.body).on('click', ".resultBatch", function(e){
		pressNavigable(this);
	});
	
	// Добавяне 
	$(document.body).on('click', ".textResult", function(e){
		var url = $(this).attr("data-url");
		var params = {recId:getSelectedRowId()};
		
		processUrl(url, params);
	});
	
	/**
	 * При спиране на писането в полето за търсене
	 * @param e
	 * @returns
	 */
	$(document.body).on('keyup', ".large-field", function(e){
		
		// @todo да се намери по красиво решение
		if($(".buttonOverlay").css('display') != 'none'){
			return;
		}
		
		// Хак да не се тригърва ивента при натискане на ентър или при навигацията на страницата за избор на селектиран елемент
		if(e.key == "Enter" || e.key == "ArrowRight" || e.key == "ArrowLeft" || e.key == "ArrowUp" || e.key == "ArrowDown"  || e.key == "PageUp" || e.key == "PageDown" || e.key == 'Alt') return;
		
		activeInput = true;

		// След всяко натискане на бутон изчистваме времето на изчакване
		clearTimeout(timeout);

		var url = $(this).attr("data-keyupurl");
		if(!url){
			return;
		}

		var inpVal = $(this).val();

		var operation = getSelectedOperation();

		if(isNumberOperation(inpVal) && operation == 'add'){
			
			console.log('QUANTITY OPERATION WAITING');
			return;
		}
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");

		// Правим Ajax заявката като изтече време за изчакване
		timeout = setTimeout(function(){
			resObj = new Object();
			resObj['url'] = url;

			var params = {operation:operation,search:inpVal,recId:selectedRecId};
			processUrl(url, params);

		}, 2000);
	});
	
	
	// Направата на плащане след натискане на бутон
	$(document.body).on('click', ".payment", function(e){
		pressNavigable(this);
	});

	$('body').on('paste', '.large-field', function (e){
		activeInput = true;
	});
	
	// Бутоните за приключване приключват бележката
	$(document.body).on('click', ".closeBtns", function(e){
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		var data = {receipt:receiptId};
		
		processUrl(url, params);
	});
	
	// При клик на бутон изтрива запис от бележката
	$(document.body).on('click', ".deleteRow", function(e){
		deleteSelectedElement();
	});
	
	// При клик на бутон добавя отстъпка
	$(document.body).on('click', ".discountBtn", function(e){
		pressNavigable(this);
	});

	// Избор на контрагент
	$(document.body).on('click', ".posResultContragent, .contragentLinkBtns", function(e){
		pressNavigable(this);
	});
	

	
	// Попълване на символи от клавиатурата
	$(document.body).on('click', ".keyboard-btn", function(e){

		var currentAttrValue = $(this).val();
		
		// Ако е натиснат бутон за смяна на език
		if($(this).hasClass('keyboard-change-btn')) {
			var lang = $(this).attr('data-klang');
			sessionStorage.setItem('activeKeyboard', lang);
			$('.keyboard#' + lang).show().siblings('.keyboard').hide();
			return;
		}

		inputChars($('.keyboardText'), currentAttrValue);

		if (currentAttrValue == "ENTER") {
			$('.select-input-pos').val($('.keyboardText').val());
			$('.ui-dialog-titlebar-close').click();
		}
	});

	$(document.body).on('click', ".ui-dialog-titlebar-close", function() {
		if($('.keyboardText').val()){
			$('.select-input-pos').val($('.keyboardText').val());
			var e = jQuery.Event("keyup");
			$('.select-input-pos').trigger(e);
			activeInput = true;
		}
		
		openedModal = false;
	});

	document.addEventListener("keydown", function(event) {
		
		if(event.key == "ArrowUp"){
			arrowUp();
		}

		if(event.key == "ArrowDown") {
			arrowDown();
		}

		if(event.key == "ArrowLeft") {
			arrowLeft();
		}
		if(event.key == "ArrowRight") {
			arrowRight();
		}
		
		if(event.key == "PageDown"){
			pageDown();
		}
		if(event.key == "PageUp"){
			pageUp();
		}

		if(event.key == "Enter"){
			enter();
		}
	});

	document.addEventListener("keyup", function(event) {
		if(event.key == "Control"){
			hideHints();
		}
	});
	startNavigation();



	// действие на бутоните за действията
	$(document.body).on('click', ".operationBtn", function(e){
		var operation = $(this).attr("data-value");
		var selectedRecId = getSelectedRowId();
		
		doOperation(operation, selectedRecId);
	});

	
	
	
	
	
	
	var eventType;
	if (isTouchDevice()) {
		eventType = "click";
	} else {
		eventType = "click";
	}

	// Добавяне на продукт от резултатите за търсене
	$(document.body).on(eventType, ".pos-add-res-btn", function(e){
		pressNavigable(this);
	});


	// При натискане на бутон с резултати да се чисти таймаута
	$(document.body).on('click', ".posBtns", function(e){
		activeInput = false;
		clearTimeout(timeout);
	});
	
	// При прехвърляне на бележка, автоматично създаваме нова
	$(document.body).on('click', ".transferBtn", function(e){
		var url = $(this).attr("data-url");
		processUrl(url, null);
	});

	// Сменяне на селектирания ред от бележките при клик
	$(document.body).on('click', "#receipt-table .receiptRow", function(e){
		$('.highlighted').removeClass('highlighted');
		$(this).closest('.receiptRow').addClass('highlighted');
		
		var operation = getSelectedOperation();
		refreshResultByOperation($(this), operation);
	});


	// Време за изчакване
	var timeout1;


	// При натискане на бутона за задаване на цена
	$(document.body).on('click', "div.resultPrice", function(e){
		pressNavigable(this);
	});

	// При отваряне на нова бележка маха се фокусирания елемент
	$(document.body).on('click', ".openNoteBtn, .revertReceipt", function(e){
		sessionStorage.removeItem("focused");
	});
	
	// При натискане на бутона за задаване на количество/опаковка
	$(document.body).on('click', "div.resultPack", function(e){
		pressNavigable(this);
	});
	
	// При натискане на бутона за задаване на количество/опаковка
	$(document.body).on('click', "div.chooseStoreBtn", function(e){
		pressNavigable(this);
	});
	
	// При натискане на бутона за задаване на количество/опаковка
	$(document.body).on('click', "div.locationBtn", function(e){
		pressNavigable(this);
	});
	
	// При натискане на бутона за показване на подробна информация избрания елемент
	$(document.body).on('click', ".enlargeProductBtn", function(e){
		
		var element = $(this);
		openInfo(element);
	});
	
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".keyboardBtn", function(e){
		openKeyboard();
	});
	
	// При натискане на бутон за нова фирма
	$(document.body).on('click', ".newCompanyBtn", function(e){
		presssNavigable(this);
	});
	
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".helpBtn", function(e){
		openHelp();
	});
	
	$("body").setShortcutKey( CONTROL , DELETE ,function() {
		 deleteSelectedElement();
	});

	$("body").setShortcutKey( CONTROL , A ,function() {
		openProducts();
	});

	$("body").setShortcutKey( CONTROL , S ,function() {
		openQuantity();
	});

	$("body").setShortcutKey( CONTROL , Z ,function() {
		openPayment();
	});

	$("body").setShortcutKey( CONTROL , E ,function() {
		openText();
	});

	$("body").setShortcutKey( CONTROL , K ,function() {
		openClient();
	});

	$("body").setShortcutKey( CONTROL , B ,function() {
		openReceipt();
	});

	$("body").setShortcutKey( null , F2 ,function() {
		var element = $('.enlargeProductBtn');
		openInfo(element);
	});
	
	$("body").setShortcutKey( CONTROL , P ,function() {
		openPrint();
	});

	$("body").setShortcutKey( CONTROL , V ,function() {
		openKeyboard();
	});

	$("body").setShortcutKey( CONTROL , O ,function() {
		openReject();
	});

	$("body").setShortcutKey( CONTROL , X ,function() {
		logout();
	});

	$("body").setShortcutKey( null , F1 ,function() {
		openHelp();
	});

	$("body").setShortcutKey( CONTROL , I ,function() {
		deteleElements();
	});

	var timeoutAlt;
	$("body").setShortcutKey(null,  CONTROL,function() {
		showHints();
	});

	// При натискане на бутона за клавиатура
	$(document.body).on('click',  function(e){
		hideHints();
	})

}

function inputChars(inputElement, val) {
	var strOffset = 1;
	var inpVal = $(inputElement).val();
	var start = inputElement[0].selectionStart;
	var end = inputElement[0].selectionEnd;

	if(val == '.' && inpVal == ""){
		inpVal = "0.";
		strOffset = 2;
	} else if(val == '«') {
		if (start == end) {
			inpVal =inpVal.substr(0, start-1) + inpVal.substr(end);
			strOffset = -1;
		} else {
			inpVal =inpVal.substr(0, start) + inpVal.substr(end);
			strOffset = 0;
		}
	} else {
		inpVal = inpVal.substr(0, start) + val + inpVal.substr(end);
	}

	inputElement.val(inpVal);
	inputElement[0].selectionStart = start + strOffset;
	inputElement[0].selectionEnd = start + strOffset;

	if($('body').hasClass('wide')){
		inputElement.focus();
	}

	var e = jQuery.Event("keyup");
	inputElement.trigger(e);

	activeInput = true;
}


function deteleElements(){
	$('.rejectBtn').parent().trigger("click");
}

// Активиране на лупата за увеличение
function openInfo(element) {
	
	var url = element.attr("data-url");
	url = (element.hasClass('disabledBtn')) ? null : url;
	
	var enlargeClassId = element.attr("data-enlarge-class-id");
	var enlargeObjectId = element.attr("data-enlarge-object-id");
	var enlargeTitle = element.attr("data-modal-title");
	
	var params = {enlargeClassId:enlargeClassId,enlargeObjectId:enlargeObjectId};
	processUrl(url, params);
	
	if(url){
		openModal(enlargeTitle, "defaultHeight");
	}
}

// Отваря модал с хелпа
function openHelp() {
	var url = $('.helpBtn').attr("data-url");
	processUrl(url, null);
	
	var modalTitle = $('.helpBtn').attr("data-modal-title");
	openModal(modalTitle);
}

function showHints(){
	if ($('.buttonOverlay').css('display') == "none") {
		$('.buttonOverlay').fadeIn();
	}
}

function hideHints(){
	$('.buttonOverlay').fadeOut();
}

function openReceipt() {
	selectedRecId = getSelectedRowId();
	doOperation("receipts", selectedRecId)
}

// Отваря виртуалната клавиатура
function openKeyboard() {
	var url = $('.keyboardBtn').attr("data-url");
	var string = $("input[name=ean]").val();
	
	var params = {string:string};
	processUrl(url, params);
	
	var modalTitle = $('.keyboardBtn').attr("data-modal-title");
	openModal(modalTitle, "smallHeight");
}

function openPrint() {
	$('.printBtn').click();
}
function openClient() {
	var selectedRecId = getSelectedRowId();
	doOperation("contragent", selectedRecId);
}

function logout() {
	var url = $('.logout.operationHolder').closest('a').attr("href");
	location.href = url;
}

function openReject() {
	if ($('.rejectBtn').length) {
		$('.rejectBtn').parent().click();
	}
}
function openText() {
	var selectedRecId = getSelectedRowId();
	doOperation("text", selectedRecId);
}

function openProducts() {
	var selectedRecId = getSelectedRowId();
	doOperation("add", selectedRecId);
}

function openQuantity() {
	var selectedRecId = getSelectedRowId();
	doOperation("quantity", selectedRecId);
}

function openPayment() {
	var selectedRecId = getSelectedRowId();
	doOperation("payment", selectedRecId);
}

// Калкулира ширината
function calculateWidth(){
	var winWidth = parseInt($(window).width());
	var winHeight = parseInt($(window).height());

	//задаване на ширина на двете колони
	$('.result-content').css('width', winWidth - $('#single-receipt-holder').width());

	//височина за таблицата с резултатите
	var receiptHeight = winHeight -  $('.tools-content').height() - $('.paymentBlock').height();
	$('.scrolling-vertical').css('height',receiptHeight);

	var headerHeight = $('.headerContent').outerHeight();
	$('#result-holder').css('height',winHeight - headerHeight);
	$('#result-holder, #single-receipt-holder').css('top',headerHeight);
}

// Направа на плащане
function doPayment(url, type){
	if(!url || !type) return;
	var amount = $("input[name=ean]").val();
	if(!amount){
		amount = $("input[name=ean]").attr('data-defaultpayment');
	}
	
	var data = {amount:amount, type:type};
	processUrl(url, data);

	$("input[name=ean]").val("");
}

// При натискане на pageUp
function pageUp(){
	activeInput = false;
	var current = $('#receipt-table .receiptRow.highlighted');
	sessionStorage.setItem('lastHighlighted', current.attr('data-id'));
	
	if(current.length && $(current).prev('.receiptRow').length) {
		var newElement = $(current).prev('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		getCurrentElementFromSelectedRow(newElement);
	}
}

// При натискане на pageDown
function pageDown(){
	activeInput = false;
	var current = $('#receipt-table .receiptRow.highlighted');
	sessionStorage.setItem('lastHighlighted', current.attr('data-id'));
	
	if(current.length && $(current).next('.receiptRow').length) {
		var newElement = $(current).next('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		getCurrentElementFromSelectedRow(newElement);
	}
}

// При селектиране на текущ елемент
function getCurrentElementFromSelectedRow(element){
	var operation = getSelectedOperation();
	sessionStorage.removeItem("focused");
	
	refreshResultByOperation(element, operation);
	
	if(operation != 'quantity'){
		scrollAfterKey();
	}
}

function refreshResultByOperation(element, operation){
	
	sessionStorage.removeItem("focused");
	
	// Ако операцията е партидност и реда няма такава прехвърля се към артикул
	var click = operation;
	
	if(operation == 'quantity' || operation == 'payment'){
		var selectedRecId = getSelectedRowId();
		doOperation(operation, selectedRecId);
	}
}

function arrowDown(){
	activeInput = false;
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).next().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowUp(){
	activeInput = false;
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).prev().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowRight(){
	activeInput = false;
	disableOrEnableEnlargeBtn();
}

function arrowLeft(){
	activeInput = false;
	disableOrEnableEnlargeBtn();
}

// Коя е текущо селектираната операция
function getSelectedOperation()
{
	if($("select[name=operation]").length){
		var operation = $("select[name=operation]").val();
	} else {
		var operation = $("div.operationBtn.active").attr("data-value");
	}
	
	return operation;
}

// Изтриване на текущия селектиран елемент елемент
function deleteSelectedElement() {
	var selectedElement = $(".highlighted.receiptRow");
	if(!selectedElement.length) return;
	
	var warning = selectedElement.attr("data-delete-warning");
	var url = selectedElement.attr("data-delete-url");
	if(!url) return;
	
	if (!confirm(warning)){
		
		return false; 
	}
	
	selectedElement.closest('.receiptRow').css('border', '1px solid red');
	processUrl(url, null);
}


function render_prepareResult() {
	activeInput = false;
	startNavigation();

	// Бутона за увеличение да се дисейбва ако няма избран селектиран ред
	if($('.enlargeProductBtn').length){
		var selectedElement = $(".highlighted");
		
		if(selectedElement.length){
			$('.enlargeProductBtn').removeClass('disabledBtn');
		} else {
			$('.enlargeProductBtn').addClass('disabledBtn');
		}
	}
}

function render_calculateWidth(){
	calculateWidth();
}

// След презареждане

var semaphor;

function render_afterload()
{
	afterload();
	
	/*
	
	var eanInput = $("input[name=ean]");
	
	var searchVal = eanInput.val();
	var submitUrl = eanInput.attr("data-url");
	var clicked = sessionStorage.getItem('operationClicked');
	
	if(submitUrl && !semaphor && clicked){
		
		resObj = new Object();
		resObj['url'] = submitUrl;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = eanInput.attr("data-id");

		var sendAjax = true;
		if(!searchVal && (operation == 'add' || operation == 'quantity')){
			sendAjax = false;
		}
		
		semaphor = 1;
		
		if(sendAjax){
			getEfae().process(resObj, {string:searchVal,recId:selectedRecId});
		}
		
		return;
	}
	
	semaphor = 0;
	sessionStorage.removeItem("operationClicked");
	*/
}

function enter() {
	if (openedModal){
		
		console.log("Modal open: return");
		return;
	}

	clearTimeout(timeout);
	var value = $("input[name=ean]").val();
	var url = $("input[name=ean]").attr("data-url");
	
	var operation = getSelectedOperation();

	// Ако има селектиран ред в резултатите
	var element = $(".navigable.selected");

	var isOnlyQuantityString = isNumberOperation(value);
	
	//console.log(isOnlyQuantityString);
	
	// Ако има селектиран елемент в резултатите
	if(element.length){
		
		console.log(activeInput, isOnlyQuantityString, operation);
		
		// Ако инпута е активен но е с празен стринг, или е активен и е въведена операция за к-во или не е активен
		// тогава се клика на селектирания елемент в резултатите
		if((activeInput === true && !value) || (activeInput === true && operation == 'payment') || (activeInput === true && isOnlyQuantityString && operation != 'payment') || activeInput === false){
			
			pressNavigable(element);
			
			
			return;
			
			// Намира първия елемент с data-url
			/*
			var elementDataUrl = element.attr("data-url");
			var hrefUrl = element.attr("href");
			var onclick = element.attr("onclick");
			
			if(onclick){
				// Вика се клик
				var event = jQuery.Event("click");
				element.trigger(event);
				
				console.log("ENTER SUBMIT TRIGGER " + element.attr("id"));
				
				return;
			}
			
			if(hrefUrl){
				location.href = hrefUrl;
				return;
			}
			
			if(elementDataUrl == undefined){
				var child = element.find('[data-url]');

				var elementDataUrl = child.attr("data-url");
				if(elementDataUrl){
					element = child;
				}
			}

			if(elementDataUrl == undefined){
				var child = element.find('[href]');
				if(child.length){
					element = child;
				}
			}

			if(element != undefined){

				// Вика се клик
				var event = jQuery.Event("click");
				element.trigger(event);

				console.log("ENTER SUBMIT DATA_ATTR  " + element.attr("id"));
				return;
			}*/
		}
	}

	submitInputString();
}


function pressNavigable(element)
{
	var element = $(element);
	
	var params = {recId:getSelectedRowId()};
	var url = element.attr("data-url");
	
	if(element.hasClass("disabledBtn")){
		
		
		return;
	}
	
	
	if(element.hasClass('pos-add-res-btn')){
		
		addProduct(element);
		
		return;
	} else if(element.hasClass('chooseStoreBtn')) {
		var storeId = element.attr("data-storeid");
		params = {string:storeId,recId:getSelectedRowId()};
		
	} else if(element.hasClass('resultPack')) {
		var pack = element.attr("data-pack");
		if(element.hasClass("packWithQuantity")){
			var quantity = element.attr("data-quantity");
		} else {
			var quantity = $("input[name=ean]").val();
		}
		
		quantity = (quantity) ? quantity : 1;
		var string = quantity + " " + pack;
		params = {string:string,recId:getSelectedRowId()};
	} else if(element.hasClass('payment')){
		var type = element.attr("data-type");
		type = (!type) ? '-1' : type;
		doPayment(url, type);
		return;
		
	} else if(element.hasClass('contragentLinkBtns') || element.hasClass('posResultContragent')){
		
		clearTimeout(timeout);
		
		if(element.hasClass("openInNewTab")){
			window.open(url, '_blank');
			var reloadUrl = element.attr("data-reloadurl");
			
			if(reloadUrl){
				document.location = reloadUrl;
			} else {
				location.reload();
			}
		} else {
			document.location = url;
		}
		return;
		
	} else if(element.hasClass("newCompanyBtn") || element.hasClass("locationBtn")){
		location.href = url;
		return;
	} else if(element.hasClass("deleteRow")){
		deleteSelectedElement();
		return;
	} else if(element.is("a")){
		var hrefUrl = element.attr("href");
		var onclick = element.attr("onclick");
		
		if(onclick){
			var event = jQuery.Event("click");
			element.trigger(event);
			return;
		}
		
		location.href = hrefUrl;
		return;
	}
	
	console.log(url, params);
	processUrl(url, params);
	
}


/**
 * Събмитва въведеното от глобалния инпут, ако има какво и има урл
 */
function submitInputString()
{
	var value = $("input[name=ean]").val();
	var url = $("input[name=ean]").attr("data-url");
	
	if(!url){
		console.log("ENTER NO URL RETURN");
		return;
	}

	console.log("ENTER SUBMIT STRING:" + value);
	
	var params = {string:value,recId:getSelectedRowId()};
	processUrl(url, params);
}


// Дали подадения стринг е операция за задаване на количество
function isNumberOperation(string)
{
	var string = $.trim(string);
	
	// Ако има въведен непразен стринг
	if(string){
		
		// и той завършва с *
		if(string.endsWith("*")){
			
			// Премахваме * да остане чист стринг
			var quantity = string.replace("*", "");
			
			// Ако останалата част от стринга е положително число
			if($.isNumeric(quantity) && quantity > 0){
				
				return true;
			}
		}
	}
	
	// Ако се стигне до тук, значи операцията не е за промяна на количеството
	return false;
}

var openedModal;

// Отваря модала
function openModal(title, heightModal) {
	clearTimeout(timeout);
	
	// Изчистване на предишното съдържание на модала, да не се визуализира, докато се зареди новото
	$("#modalContent").html("");
	
	var height = (heightModal == "smallHeight" ) ?  500 : 700;

	dialog = $("#modalContent").dialog({
		autoOpen: false,
		height: height,
		width: 1000,
		modal: true,
		title: title,
		beforeClose: event.preventDefault(),
		close: function () {openedModal = false;},
	});

	dialog.dialog( "open" );

	setTimeout(function(){
			if ($('.keyboard'.length)) {
				var keyboard = sessionStorage.getItem('activeKeyboard');
				if (!keyboard) {
					keyboard = "keyboard-lat";
				}
				$('.keyboard#' + keyboard).show().siblings('.keyboard').hide();
			}

		},10);

	openedModal = true;

	setTimeout(function(){
			$('form').find('*').filter(':input:visible:first').focus();
		},
	500);
}

function startNavigation() {
	if($('.navigable').length) {
		
		var focused = sessionStorage.getItem('focused');

		// ръчно избирам първия елемент за селектед
		if(!focused && $('.navigable.selected').length == 0){
			focused = $('.navigable').first();
			focused.addClass('selected');
			sessionStorage.setItem('focused', focused.attr('id'));
		}

		setTimeout(function(){
			if (focused && document.getElementById(focused) && $('.navigable.selected').length == 0) {
				$('.selected').removeClass('selected');
				$('#' + focused ).addClass('selected');
			}
		});

		$('#result-holder .navigable').keynav();
	}
}
function isItVisible(element){
	var viewportWidth = $(window).width(),
		viewportHeight = $(window).height(),
		documentScrollTop = $(document).scrollTop(),
		documentScrollLeft = $(document).scrollLeft(),


		elementOffset = element.offset(),
		elementHeight = element.height(),
		elementWidth = element.width(),

		minTop = documentScrollTop,
		maxTop = documentScrollTop + viewportHeight,
		minLeft = documentScrollLeft,
		maxLeft = documentScrollLeft + viewportWidth;

	return (elementOffset.top > minTop && elementOffset.top + elementHeight < maxTop) &&
		(elementOffset.left > minLeft && elementOffset.left + elementWidth < maxLeft);
}

function scrollToHighlight(){
	if ($(".highlighted").length) {
		$(".highlighted")[0].scrollIntoView();
	}
}


function render_scrollToHighlight() {
	scrollToHighlight();
}

function scrollAfterKey(){
	if ($(".highlighted").length) {
		$(".highlighted")[0].scrollIntoView();
	}
}

// Добавя хинт
function setInputPlaceholder() {
	var activeElement = $("div.operationBtn.active");
	var operation = getSelectedOperation();
	var placeholder = activeElement.attr("title");
	
	if(operation == 'payment'){
		var defaultPayment = $("input[name=ean]").attr("data-defaultpayment");
		if(defaultPayment){
			placeholder = defaultPayment;
		}
	}
	
	$("input[name=ean]").attr("placeholder", placeholder);	
}

function afterload() {
	setInputPlaceholder();
	disableOrEnableEnlargeBtn();
}

// Активира или закрива бутона за подробна информация на артикула
function disableOrEnableEnlargeBtn()
{
	var focusedElement = $(".navigable.selected");

	if(focusedElement.length){
		if(focusedElement.hasClass('enlargable')){
			var enlargeClassId = focusedElement.attr("data-enlarge-class-id");
			var enlargeObjectId = focusedElement.attr("data-enlarge-object-id");
			var enlargeTitle= focusedElement.attr("data-modal-title");

			if(enlargeClassId && enlargeObjectId && enlargeTitle) {
				$(".enlargeProductBtn").removeClass('disabledBtn');
				$(".enlargeProductBtn").removeAttr("disabled");

				$(".enlargeProductBtn").attr('data-modal-title', enlargeTitle);
				$(".enlargeProductBtn").attr('data-enlarge-class-id', enlargeClassId);
				$(".enlargeProductBtn").attr('data-enlarge-object-id', enlargeObjectId);
			}

		} else {
			$(".enlargeProductBtn").addClass('disabledBtn');
			$(".enlargeProductBtn").attr('disabled', 'disabled');
		}
	} else {
		$(".enlargeProductBtn").addClass('disabledBtn');
		$(".enlargeProductBtn").attr('disabled', 'disabled');
	}
}

function addProduct(el) {
	clearTimeout(timeout);

	var elemRow = $(el).closest('.receiptRow');
	$(elemRow).addClass('highlighted');
	var url = $(el).attr("data-url");
	var productId = $(el).attr("data-productId");
	var data = {productId:productId};

	// При добавяне на артикул ако в инпута има написано число или число и * да го третира като число
	var quantity = $("input[name=ean]").val();
	quantity = $.trim(quantity);
	quantity = quantity.replace("*", "");

	// Подаване и на количеството от инпута
	if(quantity && $.isNumeric(quantity) && quantity > 0){
		data.string = quantity;
	}

	processUrl(url, data);
	calculateWidth();

	activeInput = false;
}





function processUrl(url, params) {
	if(!url) return;

	resObj = new Object();
	resObj['url'] = url;

	if(params){
		getEfae().process(resObj, params);
	} else {
		getEfae().process(resObj);
	}
}


/**
 * Кой е селектирания ред
 */
function getSelectedRowId()
{
	var selectedElement = $(".highlighted.productRow");
	
	return selectedElement.attr("data-id");
}


/**
 * Извършва подадената операция
 */
function doOperation(operation, selectedRecId)
{
	clearTimeout(timeout);
	
	sessionStorage.removeItem("focused");
	var currentlySelected = getSelectedOperation();
	sessionStorage.setItem('lastSelectedOperation', currentlySelected);
	var lastHighlighted = sessionStorage.getItem('lastHighlighted');
	
	var operationBtn = $('.operationBtn[data-value="'+operation+'"]');
	
	var selectedRecId = getSelectedRowId();
	sessionStorage.setItem('lastHighlighted', selectedRecId);
	
	var url = operationBtn.attr("data-url");
	var disabled = operationBtn.hasClass("disabledBtn");
	
	if(!url || disabled){
		
		return;
	}
	
	// ако операцията е същата но стринга е празен да не се изпълнява заявката
	if(operation == currentlySelected){
		
		submitInputString();
		
		
		return;
	}
	
	$("input[name=ean]").val("");
	
	sessionStorage.setItem('operationClicked', true);
	var data = {operation:operation,recId:selectedRecId};
	if(activeInput){
		data.search = string;
	}
	
	processUrl(url, data);
	
	activeInput = false;
	scrollToHighlight();
}