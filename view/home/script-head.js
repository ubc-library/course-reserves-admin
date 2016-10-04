$(document).ready(function () {
    var countChecked = function () {

        var book = $("input.new-books-print-control:checked").length;
        $(".new-books-selected-count").text((book === 0 ? "None" : book));
        //console.log(book);

        var dvd = $("input.new-streamingmedia-print-control:checked").length;
        $(".new-streamingmedia-selected-count").text((dvd === 0 ? "None" : dvd));
        //console.log(dvd);

        var ebook = $("input.new-cannotbedetermined-print-control:checked").length;
        $(".new-cannotbedetermined-selected-count").text((ebook === 0 ? "None" : ebook));
        //console.log(ebook);

        var phys = $("input.new-physical-print-control:checked").length;
        $(".new-physical-selected-count").text((phys === 0 ? "None" : phys));
        //console.log(phys);

        var total = book + dvd + ebook + phys;

        $(".pick-slip-count").text(total);
        updatePrintButton(total)
    };

    function updatePrintButton(total) {
        if (total > 0) {
            $('.trigger-print-pickslip').removeAttr('disabled', 'disabled').addClass('orange');
            $(document.body).addClass('dummyClass').removeClass('dummyClass');
        }
        else {
            $('.trigger-print-pickslip').attr('disabled', 'true').removeClass('orange');
            $(document.body).addClass('dummyClass').removeClass('dummyClass');
        }
    }

    countChecked();

    function delayCount() {
        setTimeout(countChecked, 1);
    }


    $("input[class$='-print-control']").lazybind("click", countChecked, 15);
    //$( "input[class$='-print-control']" ).on( "click", delayCount);


    $(".trigger-print-pickslip").click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        var idsToPrint = new Array();
        $("table[class$='-new-table-entries'] input[class$='-print-control']:checked").each(function () {
            idsToPrint.push($(this).attr('id') + ':' + $(this).data('course'));
        });
        var ids = {};
        for (var i = 0; i < idsToPrint.length; ++i) {
            if (idsToPrint[i] !== undefined) {
                ids[i] = idsToPrint[i];
            }
        }

        $('#pick-slip-modal').reveal({
            animation: 'fadeAndPop',
            animationspeed: 300,
            closeonbackgroundclick: false,
            dismissmodalclass: 'close-reveal-modal',
            open: function () {
                $('#pick-slip-generation-status').append('<br />Status: Pick Slips are processing <em>(this could take ahwile...)</em>');
            },
            opened: function () {
                $('html,body').css('overflow', 'hidden');
                $.ajax({
                    url: window.base_url + "/pickslips",
                    type: "POST",
                    data: {itemids: idsToPrint, suffix: $('#location-filter').text()}
                })
                    .done(function (pdfurl) {
                        $('#pick-slip-generation-status').append('<br />Status: Pick Slips opening in new window, ready for printing');
                        window.open(pdfurl, '_blank', menubar = 0, top = 0, left = 0);
                    })
                    .fail(function () {
                        alert('The system could not process the ItemIDs');
                    })
                    .always(function () {
                        $('#pick-slip-generation-status').append('');
                        $('#pickslips-confirm').removeAttr('disabled').attr('enabled', 'enabled');
                        $('#pickslips-cancel').on('click', function () {
                            location.reload();
                        });
                    });
            },
            close: function () {
                $('html,body').css('overflow', 'initial');
                $('#ui-datepicker-div').css('display', 'none');

            }
        });
    });
});

function getArrayOfCourseIds(stringOfCourseIds) {
    var regex = new RegExp("(\\d{1,})|(?:[a-zA-Z\\-_\\.0-9]{1,},{0,1})", "g");
    var match;
    var cids = [];
    while ((match = regex.exec(stringOfCourseIds)) != null) {
        if (match.index === regex.lastIndex) {
            ++regex.lastIndex;
        }
        //match[0] is always a matching group
        //match[1] only exists for remembered (sub)groups
        if (typeof match[1] !== "undefined") {
            cids.push(match[1]);
        }
    }
    if (debug) {
        console.log("Found the following CourseIDs: " + cids);
    }
    return cids;
}

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}


window.pageDatatables = {};

var aTableHasLoaded = false;

// do not remove!
//in process queues, to change status id
window.changeStatusOptionStatusId = {};
window.statusChangeSelected = {};

if (typeof window.setCopyrightInProcessQueueDropDownState === 'undefined') {
    window.setCopyrightInProcessQueueDropDownState = function (currentStatusId, currentState, newState) {
        var _label = '#vstatus_'+ currentStatusId +'-status-change-indicator' + '-label';
        var _caret = '#vstatus_'+ currentStatusId +'-status-change-indicator' + '-caret';
        var _k = 'status_' + currentStatusId;
        var _save = '#change-status-status_' + currentStatusId + '-save';

        $(_label).removeAttr(currentState).attr(newState, newState);
        $(_caret).removeAttr(currentState).attr(newState, newState);

        if(newState == 'enabled'){
            $(_label).text('Select a status');
        }

        if(newState == 'disabled'){
            $(_label).text('Select items first...');
            $(_save).removeAttr(currentState).attr(newState, newState);
            statusChangeSelected[_k].selected = [];
            changeStatusOptionStatusId[_k] = '';
        }
    }
}

// do not remove!
//the div captures the drop down and preventds you from seeing a ,ong list
if (typeof window.toggleAccordionVisibility === 'undefined') {
    window.toggleAccordionVisibility = function (selector, control) {
        setTimeout(function () {
            if ($(control).hasClass("open")) {
                $(selector).css('overflow', 'visible');
            }
            else {
                $(selector).css('overflow', 'hidden');
            }
        }, 50);
    }
}

// do not remove!
if (typeof window.setChangeStatusOption === 'undefined') {
    window.setChangeStatusOption = function (currentStatusId, newStatusId) {
        var _k = 'status_' + currentStatusId;
        changeStatusOptionStatusId[_k] = '';
        changeStatusOptionStatusId[_k] = newStatusId;
        var _label = '#vstatus_' + currentStatusId + '-status-change-indicator-label';
        var _save = '#change-status-status_' + currentStatusId + '-save';
        var _trigger = '#li-status-change-' + newStatusId;
        $(_label).text($(_trigger).text());
        $(_save).removeAttr('disabled').attr('enabled', 'enabled');
    }
}

// do not remove!
if (typeof window.saveChangeStatusOption === 'undefined') {
    window.saveChangeStatusOption = function (currentStatusId) {
        var _k = 'status_' + currentStatusId;
        console.log('Current Status: ' + currentStatusId);
        console.log('New Status: ' + changeStatusOptionStatusId[_k]);
        console.log(statusChangeSelected[_k].selected);
        batchItemStatusUpdate(statusChangeSelected[_k].selected, changeStatusOptionStatusId[_k]);
    }
}
