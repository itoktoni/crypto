<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>RegistrationForm_v9 by Colorlib</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<link rel="stylesheet" href="{{ url('build/app-WSrpXqtN.css') }}" type="text/css">

		<style>
			.wrapper {
				background: url(/storage/{{ env('APP_BACKGROUND') }}) no-repeat right center;
			}
		</style>
	</head>

	<body>

		<div class="wrapper">
			<div class="inner">

                @yield('content')

			</div>
		</div>


	</body>
</html>
