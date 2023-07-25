<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran</title>
</head>
<body onload="pay()">
    <script
        type="text/javascript"
        src="https://app.midtrans.com/snap/snap.js"
        data-client-key="Mid-client-_KJ2AWCZJmtSDYmk"
    ></script>
    <script type="text/javascript">
        function pay() {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const token = urlParams.get('token');
            const pay = urlParams.get('pay');
            if (token) {
                window.snap.show();
                window.snap.pay(token, {
                    onSuccess: () => {
                        window.ReactNativeWebView.postMessage('success');
                    },
                    onPending: function() {
                        window.ReactNativeWebView.postMessage('pending');
                    },
                    onError: function(){
                        window.ReactNativeWebView.postMessage('error');
                    },
                    onClose: function(){
                        window.ReactNativeWebView.postMessage('close');
                    }
                });
            }

            if (pay === 'success') {
                window.ReactNativeWebView.postMessage('success');
            } else if (pay === 'pending') {
                window.ReactNativeWebView.postMessage('pending');
            } else if (pay === 'pending') {
                window.ReactNativeWebView.postMessage('error');
            }
        }
    </script>
</body>
</html>