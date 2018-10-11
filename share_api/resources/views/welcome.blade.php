<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }


            /** 小孩动画**/
            @keyframes run {
                0% {
                    background-position: 0 0;
                }
                100% {
                    background-position: -6000px 0;
                }
            }
            @-webkit-keyframes run {
                0% {
                    background-position: 0 0;
                }
                100% {
                    background-position: -6000px 0;
                }
            }
            #sprite {
                margin: 20px auto;
                width: 75px;
                height: 90px;
                background: url("{{asset('images/boy.png')}}") 0 0 no-repeat;
                -webkit-animation: run 1s steps(80) infinite;/*80帧*/
                animation: run 1s steps(80) infinite;
            }



            .audio_play_area{
                margin: 20px auto;
            }
            .icon_audio_default {
                background: transparent url("{{asset('images/iconloop.png')}}") no-repeat 0 0;
                width: 18px;
                height: 25px;
                vertical-align: middle;
                display: inline-block;
                -webkit-background-size: 54px 25px;
                background-size: 54px 25px;
                background-position: -36px center;
            }
            .icon_audio_playing {
                background: transparent url("{{asset('images/iconloop.png')}}") no-repeat 0 0;
                width: 18px;
                height: 25px;
                vertical-align: middle;
                display: inline-block;
                -webkit-background-size: 54px 25px;
                background-size: 54px 25px;
                -webkit-animation: audio_playing 1s infinite;
                background-position: 0px center;
            }
            @keyframes audio_playing {
                30% {
                    background-position: 0px center;
                }
                31% {
                    background-position: -18px center;
                }
                61% {
                    background-position: -18px center;
                }
                61.5% {
                    background-position: -36px center;
                }
                100% {
                    background-position: -36px center;
                }
            }
            @-webkit-keyframes audio_playing {
                30% {
                    background-position: 0px center;
                }
                31% {
                    background-position: -18px center;
                }
                61% {
                    background-position: -18px center;
                }
                61.5% {
                    background-position: -36px center;
                }
                100% {
                    background-position: -36px center;
                }
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">

            <div class="content">
                <div class="title m-b-md">
                    Laravel
                </div>


                <div id="sprite"></div>
            </div>
        </div>
    </body>
</html>
