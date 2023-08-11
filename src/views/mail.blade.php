<!DOCTYPE html>
<html lang="en">

<head>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap"
        rel="stylesheet"
    />
    <style>
        p {
            margin: 0px;
        }
        * {
            font-family: "Poppins", sans-serif;
        }
        .animate-charcter {
            text-transform: uppercase;
            background-image: linear-gradient(
                -225deg,
                #231557 0%,
                #44107a 29%,
                #fc7e00 67%,
                #61de07 100%
            );
            background-size: auto auto;
            background-clip: border-box;
            background-size: 200% auto;
            color: #fff;
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textclip 2s linear infinite;
            display: inline-block;
        }

        @keyframes textclip {
            to {
                background-position: 200% center;
            }
        }
    </style>
</head>
<body>

<div
    style="display: flex; justify-content: center; align-items: center; background-color: rgb(219, 242, 247); width: 100%; height: 100vh"
>
    <div  style="background-color: #fff ; width: 500px; padding: 30px 20px">
        <h2 class="animate-charcter" style="font-weight: bolder; margin: 0;">
            Programmer UZ
        </h2>
        <p style="padding-top: 1rem;" >Salom <b>{{$model['name']}}</b></p>
        <p>Iltimos tasdiqlash kodini kiriting!</p>
        <p style="font-size: 55px; padding: 10px 0px">{{$model['verify_code']}}</p>
    </div>
</div>
</body>

</html>
