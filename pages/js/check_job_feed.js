var Notify = window.Notify.default;
$(function(){
    if (!Notify.needsPermission) {
        doNotification(0,true);
    } else if (Notify.isSupported()) {
        Notify.requestPermission(onPermissionGranted, onPermissionDenied);
    }

    get_jobs_for_checking();
    setInterval(get_jobs_for_checking, 30000);
});

function get_jobs_for_checking() {
    $.get( "get_jobs_as_json.php", { status: "transcribed_not_checked" },
        function( data ) {
            if (data.status == 'ok') {
                load_job_panel(data,'c');
                var number_new = count_new_jobs(data);
                if (number_new > 0) {
                    doNotification(number_new);

                }
            }

        },
        "json"  );

}



function doNotification (number_jobs,b_test) {

    var mess = number_jobs + ' transcriptions are finished';
    var title = 'New Transcriptions !';

    if (number_jobs = 1) {
        mess = number_jobs + ' Transcription is ready';
        title = 'New Transcription !';
    }

    if (b_test) {
        mess = ' Seems to work ok, and because this appears this means when new transcriptions are finished, the alerts will show up';
        title = 'Test Message!'
    }
    var myNotification = new Notify(title, {
        body: mess,
        tag: 'HT',
        notifyShow: onShowNotification,
        notifyClose: onCloseNotification,
        notifyClick: onClickNotification,
        notifyError: onErrorNotification,
        timeout: 4
        //icon: 'images/alerticon.png' //icon crashes some firefox
    });

    myNotification.show();
}

function onShowNotification () {
    //console.log('notification is shown!');
}

function onCloseNotification () {
    //console.log('notification is closed!');
}

function onClickNotification () {
    //console.log('notification was clicked!');
}

function onErrorNotification () {
    //console.error('Error showing notification. You may need to request permission.');
}

function onPermissionGranted () {
    //console.log('Permission has been granted by the user');
    doNotification(0,true);
}

function onPermissionDenied () {
    //console.warn('Permission has been denied by the user');
}


$('#start-notifications').click(function() {


    if (!Notify.needsPermission) {
        doNotification(0,true);
    } else if (Notify.isSupported()) {
        Notify.requestPermission(onPermissionGranted, onPermissionDenied);
    }
});




