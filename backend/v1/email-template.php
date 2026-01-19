<!--<html>
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Playfair+Display:wght@400;700&family=JetBrains+Mono:wght@600&display=swap"
          rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap');

        * {
            box-sizing: border-box;
            font-family: "Playfair Display", sans-serif;
        }

        body {
        }

        .email-content {
            width: 100%;
            max-width: 700px;
            min-width: 300px;
            margin: auto;
            padding: 0px;
            background-color: #ffffff;
            color: #269dff;
            border-radius: 16px;

            box-sizing: border-box;
            overflow: hidden;

            box-shadow: 0 4px 16px rgba(38, 157, 255, 0.5);
            border: 1px solid rgba(38, 157, 255, 0.2);
        }

        .header {
            width: 100%;
            text-align: center;
            background-color: #cdf1de;
            padding: 32px;
        }

        .separator {
            height: 10px;
            width: 100%;

            display: flex;
            flex-direction: row;
        }

        .purple {
            background-color: #8f7fff;
        }

        .yellow {
            background-color: #ffea05;
        }

        .red {
            background-color: #f01b00;
        }

        .blue {
            background-color: #269dff;
        }

        .gray {
            background-color: #dfdfdf;
        }

        .green {
            background-color: #03bb5c;
        }

        .brown {
            background-color: #b06800;
        }

        #emoticolor-logo--image {
            width: 50%;
            min-width: 150px;
            height: auto;
            display: block;

            margin: 0px auto;
        }

        .main h1 {
            font-size: 1.6em;
            text-align: center;
            font-weight: 700;
            font-family: "Inter", sans-serif;
            background-color: #269dff;
            color: #ffffff;
            margin: 0px;
            padding: 16px;
        }

        .main .section {
            text-align: left;
            padding: 16px;
            font-size: 1.2em;
            background-color: #ffffff;
            color: #269dff;
            font-family: "Inter", sans-serif;
        }

        .main .code {
            display: block;
            text-align: center;
            font-size: 2em;
            padding: 16px;
            background-color: #03bb5c;
            color: #ffffff;
            font-weight: 600;
            font-family: "JetBrains Mono", monospace !important;
            margin: 8px;
            border-radius: 8px;
        }

        .footer {
            padding: 16px 16px;
            font-family: "Playfair Display", sans-serif;
            background-color: #cdf1de;
            color: #269dff;
            border-top: 4px solid #269dff;

            text-align: center;
        }

        .monospace-text {
            font-family: "JetBrains Mono", monospace !important;

            background-color: inherit;
            color: inherit;
        }

        .title-text {
            font-family: "Inter", sans-serif !important;

            background-color: inherit;
            color: inherit;
        }

        .body-text {
            font-family: "Playfair Display", serif !important;

            background-color: inherit;
            color: inherit;
        }

        .small-text {
            font-size: 0.7em;

            background-color: inherit;
            color: inherit;
        }

        .center-text {
            text-align: center;

            background-color: inherit;
            color: inherit;
        }

        .hidden {
            display: none !important;
        }

        .hidden-small {
            padding: 0px !important;
            margin: 0px !important;
            height: 0px;
            opacity: 0.8;
            background-color: #ffffff;
        }

        .light-green-section {
            background-color: #cdf1de !important;
            color: #269dff;
        }
    </style>
</head>
<body>
<div class="email-content">
    <div style="width: 100%; font-size: 0; line-height: 0; white-space: nowrap;">
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #8f7fff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #ffea05;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #f01b00;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #269dff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #dfdfdf;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #03bb5c;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #b06800;"></div>
    </div>
    <div class="header">
        <img id="emoticolor-logo--image"
             alt="Emoticolor logo"
             src="https://emoticolor.org/cdn/emoticolor-logo-small.png"/>
    </div>
    <div class="main">
        <h1>
            {{section-1}}
        </h1>
        <div class="section body-text">
            Hello <b>{{username}}</b>,
            <br>
            {{section-2}}
        </div>
        <div class="code monospace-text {{hidden-code}}">
            {{code}}
        </div>
        <div class="section center-text light-green-section">
            <span class="small-text">{{section-3}}</span>
        </div>
        <div class="section body-text">
            Best regards,
            <br>
            <span class="body-text">Sav</span>
        </div>
    </div>
    <div class="footer {{hidden-ip-address}}">
        Request received from <b>{{ip-address}}</b>
    </div>
</div>
</body>
</html>-->


<!--Version more compatible with email clients:-->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Playfair+Display:wght@400;700&family=JetBrains+Mono:wght@600&display=swap"
          rel="stylesheet">
</head>
<body style="background-color: #ffffff; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

<div style="width: 100%; max-width: 700px; min-width: 300px; margin: auto; background-color: #ffffff; border-radius: 16px; overflow: auto; box-shadow: 0 4px 16px rgba(38, 157, 255, 0.5); border: 1px solid rgba(38, 157, 255, 0.2);">
    <div style="width: 100%; font-size: 0; line-height: 0; white-space: nowrap;">
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #8f7fff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #ffea05;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #f01b00;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #269dff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #dfdfdf;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #03bb5c;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #b06800;"></div>
    </div>

    <div style="padding: 32px; text-align: center; background-color: #cdf1de; color: #ffffff;">
        <img src="https://emoticolor.org/cdn/emoticolor-logo-small.png"
             alt="Emoticolor logo"
             style="min-width: 150px; max-width: 80%; width: 50%; height: auto; border: 0;display: block;margin: 0px auto;">
    </div>

    <div>
        <h1 style="font-size: 1.6em; text-align: center; font-weight: 700; font-family: 'Inter', sans-serif; background-color: #269dff; color: #ffffff; margin: 0px; padding: 16px;">
            {{section-1}}
        </h1>
    </div>

    <div style="text-align: left; padding: 16px; font-size: 1.2em; background-color: #ffffff; color: #269dff; font-family: 'Playfair Display', sans-serif;">
        Hello <b style="font-family: inherit;">{{username}}</b>,
        <br>
        {{section-2}}
    </div>

    <div style="background-color: #03bb5c; padding: 16px; color: #ffffff; text-align: center; margin: 8px; border-radius: 8px; {{hidden-code}}">
            <span style="font-family: 'JetBrains Mono', monospace; font-size: 2em; font-weight: 600;">
                {{code}}
            </span>
    </div>

    <div style="background-color: #cdf1de; font-family: 'Playfair Display', sans-serif; text-align: left; padding: 16px; font-size: 0.9em; color: #269dff;">
        {{section-3}}
    </div>

    <div style="text-align: left; padding: 16px; font-size: 1.2em; background-color: #ffffff; color: #269dff; font-family: 'Playfair Display', sans-serif;">
        Best regards,
        <br>
        Sav
    </div>

    <div style="padding: 16px; font-family: 'Playfair Display', sans-serif; background-color: #cdf1de; color: #269dff; border-top: 4px solid #269dff; text-align: center;">
        Request received from <b style="font-family: inherit;">{{ip-address}}</b>
    </div>
</div>

</body>
</html>