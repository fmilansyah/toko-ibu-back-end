<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Ubah Kata Sandi - Toko Ibu</title>

    <!-- Bootstrap core CSS -->
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="signin.css" rel="stylesheet">
</head>

<body class="text-center">
    <form class="form-signin">
        <h1 class="h3 mb-3">Toko Ibu</h1>
        <h5 class="mb-3 font-weight-normal">Ubah Kata Sandi</h5>
        <label for="newPassword" class="sr-only">Kata Sandi Baru</label>
        <input type="password" id="newPassword" class="form-control" placeholder="Kata Sandi Baru" required autofocus>
        <label for="confirmNewPassword" class="sr-only">Konfirmasi Kata Sandi Baru</label>
        <input type="password" id="confirmNewPassword" class="form-control" placeholder="Konfirmasi Kata Sandi"
            required>
        <div class="alert alert-danger" style="display: none" role="alert" id="alertConfirmNewPassword"></div>
        <button class="btn btn-lg btn-primary btn-block" id="btn-submit" type="submit">Simpan</button>
    </form>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function () {
            $('#confirmNewPassword').on('change', function () {
                let password = $('#newPassword').val();
                if ($(this).val() !== password) {
                    $('#alertConfirmNewPassword').show();
                    $('#alertConfirmNewPassword').html('Kata Sandi Tidak Sama');
                } else {
                    $('#alertConfirmNewPassword').hide();
                }
            });

            $('form').on('submit', function (e) {
                e.preventDefault();
                const queryString = window.location.search;
                const urlParams = new URLSearchParams(queryString);
                const token = urlParams.get('token');
                var fd = new FormData();
                fd.append('token', token.replace(' ', '+'));
                fd.append('password', $('#newPassword').val());
                $.ajax({
                    url: window.location.origin + '/resetpassword',
                    data: fd,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    success: function (data) {
                        alert(data.Message);
                        window.location.reload();
                    }
                });
            });
        });
    </script>
</body>

</html>