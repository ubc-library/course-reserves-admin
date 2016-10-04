if ($('#view-full-history').length) {
    $("#view-full-history").click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('#view-history-modal').reveal({
            animation: 'fade',
            animationspeed: 300,
            closeonbackgroundclick: true,
            dismissmodalclass: 'close-reveal-modal',
            open: function () {
            },
            opened: function () {
                $('#view-history-modal').css('height', $(window).height() - 243 + 'px');
            },
            close: function () {
            }
        });
    });
}

$("th h3:contains('stream_video')").html("Video Media");
$("th h3:contains('book_general')").html("Books");
$("th h3:contains('stream_music')").html("Audio Media");
$("th h3:contains('stream_general')").html("General Media");
$("th h3:contains('undetermined')").html("Undetermined/Unconverted Ares Item");


$(".lms_course_split").each(function(){
    var cids = $(this).html(), cida = cids.split(','), str = '', match, i;
    for (i = 0; i < cida.length; i++){
        match = cida[i].match(/([0-9]+)([^\(\)]+),?/i);
        if(typeof  match !== 'undefined'){
            str += '<a href="/details.course/id/' + match[1] + '" target="_blank">' + match[2] + '</a><br>';
        }
        match = [];
    }
    str.slice(0,-4);
    $(this).html(str);
});


$(function(){
  setTimeout(
    function(){
      $('#smessage').slideUp();
    },
    2000
  );
});

function purgeFiles() {
    $.ajax({
        url: "/docstore.purge"
    })
        .done(function (data) {
            notify(data.message, 'default', true, false);
        })
        .fail(function () {
            alert('The system could not start the process.');
        });
}

function purgeCache() {
    $.ajax({
        url: "/docstore.purgeCache"
    })
        .done(function (data) {
            notify(data.message, 'default', true, false);
        })
        .fail(function () {
            alert('The system could not start the process.');
        });
}