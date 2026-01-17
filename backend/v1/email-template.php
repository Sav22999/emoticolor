<!--<html>
<head>
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
            margin: -0px auto;
            padding: 0px;
            background-color: #cdf1de;
            color: #269dff;
            border-radius: 8px;

            box-sizing: border-box;
            overflow: hidden;

            box-shadow: 0 4px 16px rgba(38, 157, 255, 0.5);
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

        .separator > .bar {
            flex: 1;
        }

        .bar.purple {
            background-color: #8f7fff;
        }

        .bar.yellow {
            background-color: #ffea05;
        }

        .bar.red {
            background-color: #f01b00;
        }

        .bar.blue {
            background-color: #269dff;
        }

        .bar.gray {
            background-color: #dfdfdf;
        }

        .bar.green {
            background-color: #03bb5c;
        }

        .bar.brown {
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
            height: 2px;
            opacity: 0.8;
            background-color: #269dff;
        }
    </style>
</head>
<body>
<div class="email-content">
    <div class="separator">
        <div class="bar purple"></div>
        <div class="bar yellow"></div>
        <div class="bar red"></div>
        <div class="bar blue"></div>
        <div class="bar gray"></div>
        <div class="bar green"></div>
        <div class="bar brown"></div>
    </div>
    <div class="header">
        <img id="emoticolor-logo--image"
             alt="Emoticolor logo"
             src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+Cjxzdmcgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDk0OCAxODAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgeG1sbnM6c2VyaWY9Imh0dHA6Ly93d3cuc2VyaWYuY29tLyIgc3R5bGU9ImZpbGwtcnVsZTpldmVub2RkO2NsaXAtcnVsZTpldmVub2RkO3N0cm9rZS1saW5lam9pbjpyb3VuZDtzdHJva2UtbWl0ZXJsaW1pdDoyOyI+CiAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLC02NiwtNDUwKSI+CiAgICAgICAgPGcgaWQ9IlNxdWFyZS1CYWNrZ3JvdW5kIiB0cmFuc2Zvcm09Im1hdHJpeCg1LjYwMTQsMCwwLDUuMTQ1LC0xMzQuMTMsLTEyNzcuNjMpIj4KICAgICAgICAgICAgPHJlY3QgeD0iMjMuOTQ2IiB5PSIyNDguMzI1IiB3aWR0aD0iMTkyLjgwOSIgaGVpZ2h0PSIyMDkuOTEyIiBzdHlsZT0iZmlsbDpub25lOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8ZyBpZD0iTG9nbyIgdHJhbnNmb3JtPSJtYXRyaXgoMSwwLDAsMSwwLDM3Ni44MzkpIj4KICAgICAgICAgICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMSwwLDAsMSwtMzMuNjc0NSwtMTY2LjU2NCkiPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTE4Ny4xNzQsMzUzLjA4MUwxODcuMTc0LDM3OC43MjVDMTg2Ljg0MSwzODYuMzkyIDE4NC42MzMsMzkzLjMwOSAxODAuNTQ5LDM5OS40NzVDMTc2LjQ2Niw0MDUuNjQyIDE3MS4yMTYsNDEwLjU1OSAxNjQuNzk5LDQxNC4yMjVDMTU4LjM4Myw0MTcuODkyIDE1MS4yNTgsNDE5LjcyNSAxNDMuNDI0LDQxOS43MjVDMTM1LjQyNCw0MTkuNzI1IDEyOC4wOTEsNDE3Ljc2NyAxMjEuNDI0LDQxMy44NUMxMTQuNzU4LDQwOS45MzQgMTA5LjQ2Niw0MDQuNjg0IDEwNS41NDksMzk4LjFDMTAxLjYzMywzOTEuNTE3IDk5LjY3NCwzODQuMTQyIDk5LjY3NCwzNzUuOTc1TDk5LjY3NCwyODMuNDc1Qzk5LjY3NCwyNzUuMzA5IDEwMS42MzMsMjY3LjkzNCAxMDUuNTQ5LDI2MS4zNUMxMDkuNDY2LDI1NC43NjcgMTE0Ljc1OCwyNDkuNTE3IDEyMS40MjQsMjQ1LjZDMTI4LjA5MSwyNDEuNjg0IDEzNS40MjQsMjM5LjcyNSAxNDMuNDI0LDIzOS43MjVDMTUxLjQyNCwyMzkuNzI1IDE1OC43NTgsMjQxLjY4NCAxNjUuNDI0LDI0NS42QzE3Mi4wOTEsMjQ5LjUxNyAxNzcuMzgzLDI1NC43NjcgMTgxLjI5OSwyNjEuMzVDMTg1LjIxNiwyNjcuOTM0IDE4Ny4xNzQsMjc1LjMwOSAxODcuMTc0LDI4My40NzVMMTg3LjE3NCwzMzkuNDc1TDEzNi45MjQsMzM5LjQ3NUwxMzYuOTI0LDM3OC40NzVDMTM2LjkyNCwzODAuMTQyIDEzNy41NDksMzgxLjY0MiAxMzguNzk5LDM4Mi45NzVDMTQwLjA0OSwzODQuMzA5IDE0MS41OTEsMzg0Ljk3NSAxNDMuNDI0LDM4NC45NzVDMTQ1LjI1OCwzODQuOTc1IDE0Ni43NTgsMzg0LjMwOSAxNDcuOTI0LDM4Mi45NzVDMTQ5LjA5MSwzODEuNjQyIDE0OS42NzQsMzgwLjE0MiAxNDkuNjc0LDM3OC40NzVMMTQ5LjY3NCwzNjMuNjE0TDE4Ny4xNzQsMzUzLjA4MVpNMTM2LjkyNCwzMDQuNDc1TDE0OS42NzQsMzA0LjQ3NUwxNDkuNjc0LDI3OC40NzVDMTQ5LjY3NCwyNzYuNjQyIDE0OS4wOTEsMjc1LjE0MiAxNDcuOTI0LDI3My45NzVDMTQ2Ljc1OCwyNzIuODA5IDE0NS4yNTgsMjcyLjIyNSAxNDMuNDI0LDI3Mi4yMjVDMTQxLjU5MSwyNzIuMjI1IDE0MC4wNDksMjcyLjgwOSAxMzguNzk5LDI3My45NzVDMTM3LjU0OSwyNzUuMTQyIDEzNi45MjQsMjc2LjY0MiAxMzYuOTI0LDI3OC40NzVMMTM2LjkyNCwzMDQuNDc1WiIgc3R5bGU9ImZpbGw6cmdiKDM4LDE1NywyNTUpOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTMzLjY3NDUsLTE2Ni41NjQpIj4KICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik0xOTkuNjc0LDQxNy4yMjVMMTk5LjY3NCwyNDIuMjI1TDIyOS40MjQsMjQyLjIyNUwyMjkuNDI0LDI1MC45NzVMMjMxLjkyNCwyNTAuOTc1QzIzMi43NTgsMjQ3LjgwOSAyMzUuMTMzLDI0NS4xNDIgMjM5LjA0OSwyNDIuOTc1QzI0Mi45NjYsMjQwLjgwOSAyNDcuMjU4LDIzOS43MjUgMjUxLjkyNCwyMzkuNzI1QzI1Ni45MjQsMjM5LjcyNSAyNjEuMjU4LDI0MC44NSAyNjQuOTI0LDI0My4xQzI2OC41OTEsMjQ1LjM1IDI3MS4wMDgsMjQ3Ljk3NSAyNzIuMTc0LDI1MC45NzVMMjc0LjY3NCwyNTAuOTc1QzI3Ni4zNDEsMjQ4LjQ3NSAyNzguNzU4LDI0NS45NzUgMjgxLjkyNCwyNDMuNDc1QzI4NS4wOTEsMjQwLjk3NSAyOTAuMDkxLDIzOS43MjUgMjk2LjkyNCwyMzkuNzI1QzMwNS4yNTgsMjM5LjcyNSAzMTEuODgzLDI0Mi40NzUgMzE2Ljc5OSwyNDcuOTc1QzMyMS43MTYsMjUzLjQ3NSAzMjQuMTc0LDI2MS42NDIgMzI0LjE3NCwyNzIuNDc1TDMyNC4xNzQsNDE3LjIyNUwyODcuNDI0LDQxNy4yMjVMMjg3LjQyNCwyNzguMjI1QzI4Ny40MjQsMjc3LjIyNSAyODcuMDQ5LDI3Ni4zNSAyODYuMjk5LDI3NS42QzI4NS41NDksMjc0Ljg1IDI4NC42NzQsMjc0LjQ3NSAyODMuNjc0LDI3NC40NzVDMjgyLjY3NCwyNzQuNDc1IDI4MS43OTksMjc0Ljg1IDI4MS4wNDksMjc1LjZDMjgwLjI5OSwyNzYuMzUgMjc5LjkyNCwyNzcuMjI1IDI3OS45MjQsMjc4LjIyNUwyNzkuOTI0LDQxNy4yMjVMMjQzLjY3NCw0MTcuMjI1TDI0My42NzQsMjc4LjcyNUMyNDMuNjc0LDI3Ni4wNTkgMjQyLjQyNCwyNzQuNzI1IDIzOS45MjQsMjc0LjcyNUMyMzcuNDI0LDI3NC43MjUgMjM2LjE3NCwyNzYuMDU5IDIzNi4xNzQsMjc4LjcyNUwyMzYuMTc0LDQxNy4yMjVMMTk5LjY3NCw0MTcuMjI1WiIgc3R5bGU9ImZpbGw6cmdiKDM4LDE1NywyNTUpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTMzLjY3NDUsLTE2Ni41NjQpIj4KICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik0zODAuNDI0LDQxOS43MjVDMzcyLjQyNCw0MTkuNzI1IDM2NS4wOTEsNDE3Ljc2NyAzNTguNDI0LDQxMy44NUMzNTEuNzU4LDQwOS45MzQgMzQ2LjQ2Niw0MDQuNjg0IDM0Mi41NDksMzk4LjFDMzM4LjYzMywzOTEuNTE3IDMzNi42NzQsMzg0LjE0MiAzMzYuNjc0LDM3NS45NzVMMzM2LjY3NCwyODMuNDc1QzMzNi42NzQsMjc1LjMwOSAzMzguNjMzLDI2Ny45MzQgMzQyLjU0OSwyNjEuMzVDMzQ2LjQ2NiwyNTQuNzY3IDM1MS43NTgsMjQ5LjUxNyAzNTguNDI0LDI0NS42QzM2NS4wOTEsMjQxLjY4NCAzNzIuNDI0LDIzOS43MjUgMzgwLjQyNCwyMzkuNzI1QzM4OC40MjQsMjM5LjcyNSAzOTUuNzU4LDI0MS42ODQgNDAyLjQyNCwyNDUuNkM0MDkuMDkxLDI0OS41MTcgNDE0LjM4MywyNTQuNzY3IDQxOC4yOTksMjYxLjM1QzQyMi4yMTYsMjY3LjkzNCA0MjQuMTc0LDI3NS4zMDkgNDI0LjE3NCwyODMuNDc1TDQyNC4xNzQsMzc1Ljk3NUM0MjQuMTc0LDM4NC4xNDIgNDIyLjIxNiwzOTEuNTE3IDQxOC4yOTksMzk4LjFDNDE0LjM4Myw0MDQuNjg0IDQwOS4wOTEsNDA5LjkzNCA0MDIuNDI0LDQxMy44NUMzOTUuNzU4LDQxNy43NjcgMzg4LjQyNCw0MTkuNzI1IDM4MC40MjQsNDE5LjcyNVpNMzgwLjQyNCwzODYuNzI1QzM4Mi4yNTgsMzg2LjcyNSAzODMuNzU4LDM4Ni4xIDM4NC45MjQsMzg0Ljg1QzM4Ni4wOTEsMzgzLjYgMzg2LjY3NCwzODIuMTQyIDM4Ni42NzQsMzgwLjQ3NUwzODYuNjc0LDI3OC43MjVDMzg2LjY3NCwyNzYuODkyIDM4Ni4wOTEsMjc1LjM1IDM4NC45MjQsMjc0LjFDMzgzLjc1OCwyNzIuODUgMzgyLjI1OCwyNzIuMjI1IDM4MC40MjQsMjcyLjIyNUMzNzguNTkxLDI3Mi4yMjUgMzc3LjA0OSwyNzIuODUgMzc1Ljc5OSwyNzQuMUMzNzQuNTQ5LDI3NS4zNSAzNzMuOTI0LDI3Ni44OTIgMzczLjkyNCwyNzguNzI1TDM3My45MjQsMzgwLjQ3NUMzNzMuOTI0LDM4Mi4xNDIgMzc0LjU0OSwzODMuNiAzNzUuNzk5LDM4NC44NUMzNzcuMDQ5LDM4Ni4xIDM3OC41OTEsMzg2LjcyNSAzODAuNDI0LDM4Ni43MjVaIiBzdHlsZT0iZmlsbDpyZ2IoMzgsMTU3LDI1NSk7ZmlsbC1ydWxlOm5vbnplcm87Ii8+CiAgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMSwwLDAsMSwtMzMuNjc0NSwtMTY2LjU2NCkiPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTQ1NS42NzQsNDE3LjIyNUw0NTUuNjc0LDI3NC43MjVMNDMxLjkyNCwyNzQuNzI1TDQzMS45MjQsMjQyLjIyNUw1MTYuOTI0LDI0Mi4yMjVMNTE2LjkyNCwyNzQuNzI1TDQ5My4xNzQsMjc0LjcyNUw0OTMuMTc0LDQxNy4yMjVMNDU1LjY3NCw0MTcuMjI1WiIgc3R5bGU9ImZpbGw6cmdiKDM4LDE1NywyNTUpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDAuOTUzNTQ1LDAsLTM1NS4zMTcpIj4KICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik01MzYsNDg1Ljc5MUw1MzYsNjM1LjVMNDk4LjI1LDYzNS41TDQ5OC4yNSw0OTYuOTExTDUzNiw0ODUuNzkxWiIgc3R5bGU9ImZpbGw6cmdiKDMsMTg3LDkyKTsiLz4KICAgICAgICAgICAgPC9nPgogICAgICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwwLjk1MzU0NSwwLC0zNTUuMzE3KSI+CiAgICAgICAgICAgICAgICA8cGF0aCBkPSJNNDk4LjI1LDQ4Ni4yMzRMNDk4LjI1LDQ1MS45NzRMNTM2LDQ1MS45NzRMNTM2LDQ3NS4xMTRMNDk4LjI1LDQ4Ni4yMzRaIiBzdHlsZT0iZmlsbDpyZ2IoMywxODcsOTIpOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTMzLjY3NDUsLTE2Ni41NjQpIj4KICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik02MjQuNjc0LDQxOS43MjVDNjE2LjY3NCw0MTkuNzI1IDYwOS4zNDEsNDE3Ljc2NyA2MDIuNjc0LDQxMy44NUM1OTYuMDA4LDQwOS45MzQgNTkwLjcxNiw0MDQuNjg0IDU4Ni43OTksMzk4LjFDNTgyLjg4MywzOTEuNTE3IDU4MC45MjQsMzg0LjE0MiA1ODAuOTI0LDM3NS45NzVMNTgwLjkyNCwyODMuNDc1QzU4MC45MjQsMjc1LjMwOSA1ODIuODgzLDI2Ny45MzQgNTg2Ljc5OSwyNjEuMzVDNTkwLjcxNiwyNTQuNzY3IDU5Ni4wMDgsMjQ5LjUxNyA2MDIuNjc0LDI0NS42QzYwOS4zNDEsMjQxLjY4NCA2MTYuNjc0LDIzOS43MjUgNjI0LjY3NCwyMzkuNzI1QzYzMi44NDEsMjM5LjcyNSA2NDAuMjE2LDI0MS42ODQgNjQ2Ljc5OSwyNDUuNkM2NTMuMzgzLDI0OS41MTcgNjU4LjYzMywyNTQuNzY3IDY2Mi41NDksMjYxLjM1QzY2Ni40NjYsMjY3LjkzNCA2NjguNDI0LDI3NS4zMDkgNjY4LjQyNCwyODMuNDc1TDY2OC40MjQsMzEwLjk3NUw2MzEuNDI0LDMxMC45NzVMNjMxLjQyNCwyODEuMjI1QzYzMS40MjQsMjc5LjM5MiA2MzAuNzU4LDI3Ny44NSA2MjkuNDI0LDI3Ni42QzYyOC4wOTEsMjc1LjM1IDYyNi41MDgsMjc0LjcyNSA2MjQuNjc0LDI3NC43MjVDNjIzLjAwOCwyNzQuNzI1IDYyMS41MDgsMjc1LjM1IDYyMC4xNzQsMjc2LjZDNjE4Ljg0MSwyNzcuODUgNjE4LjE3NCwyNzkuMzkyIDYxOC4xNzQsMjgxLjIyNUw2MTguMTc0LDM3OC40NzVDNjE4LjE3NCwzODAuMTQyIDYxOC44NDEsMzgxLjY0MiA2MjAuMTc0LDM4Mi45NzVDNjIxLjUwOCwzODQuMzA5IDYyMy4wMDgsMzg0Ljk3NSA2MjQuNjc0LDM4NC45NzVDNjI2LjUwOCwzODQuOTc1IDYyOC4wOTEsMzg0LjMwOSA2MjkuNDI0LDM4Mi45NzVDNjMwLjc1OCwzODEuNjQyIDYzMS40MjQsMzgwLjE0MiA2MzEuNDI0LDM3OC40NzVMNjMxLjQyNCwzNDguNDc1TDY2OC40MjQsMzQ4LjQ3NUw2NjguNDI0LDM3NS45NzVDNjY4LjQyNCwzODQuMTQyIDY2Ni40NjYsMzkxLjUxNyA2NjIuNTQ5LDM5OC4xQzY1OC42MzMsNDA0LjY4NCA2NTMuMzgzLDQwOS45MzQgNjQ2Ljc5OSw0MTMuODVDNjQwLjIxNiw0MTcuNzY3IDYzMi44NDEsNDE5LjcyNSA2MjQuNjc0LDQxOS43MjVaIiBzdHlsZT0iZmlsbDpyZ2IoMzgsMTU3LDI1NSk7ZmlsbC1ydWxlOm5vbnplcm87Ii8+CiAgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMSwwLDAsMSwtMzMuNjc0NSwtMTY2LjU2NCkiPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTcyMy40MjQsNDE5LjcyNUM3MTUuNDI0LDQxOS43MjUgNzA4LjA5MSw0MTcuNzY3IDcwMS40MjQsNDEzLjg1QzY5NC43NTgsNDA5LjkzNCA2ODkuNDY2LDQwNC42ODQgNjg1LjU0OSwzOTguMUM2ODEuNjMzLDM5MS41MTcgNjc5LjY3NCwzODQuMTQyIDY3OS42NzQsMzc1Ljk3NUw2NzkuNjc0LDI4My40NzVDNjc5LjY3NCwyNzUuMzA5IDY4MS42MzMsMjY3LjkzNCA2ODUuNTQ5LDI2MS4zNUM2ODkuNDY2LDI1NC43NjcgNjk0Ljc1OCwyNDkuNTE3IDcwMS40MjQsMjQ1LjZDNzA4LjA5MSwyNDEuNjg0IDcxNS40MjQsMjM5LjcyNSA3MjMuNDI0LDIzOS43MjVDNzMxLjQyNCwyMzkuNzI1IDczOC43NTgsMjQxLjY4NCA3NDUuNDI0LDI0NS42Qzc1Mi4wOTEsMjQ5LjUxNyA3NTcuMzgzLDI1NC43NjcgNzYxLjI5OSwyNjEuMzVDNzY1LjIxNiwyNjcuOTM0IDc2Ny4xNzQsMjc1LjMwOSA3NjcuMTc0LDI4My40NzVMNzY3LjE3NCwzNzUuOTc1Qzc2Ny4xNzQsMzg0LjE0MiA3NjUuMjE2LDM5MS41MTcgNzYxLjI5OSwzOTguMUM3NTcuMzgzLDQwNC42ODQgNzUyLjA5MSw0MDkuOTM0IDc0NS40MjQsNDEzLjg1QzczOC43NTgsNDE3Ljc2NyA3MzEuNDI0LDQxOS43MjUgNzIzLjQyNCw0MTkuNzI1Wk03MjMuNDI0LDM4Ni43MjVDNzI1LjI1OCwzODYuNzI1IDcyNi43NTgsMzg2LjEgNzI3LjkyNCwzODQuODVDNzI5LjA5MSwzODMuNiA3MjkuNjc0LDM4Mi4xNDIgNzI5LjY3NCwzODAuNDc1TDcyOS42NzQsMjc4LjcyNUM3MjkuNjc0LDI3Ni44OTIgNzI5LjA5MSwyNzUuMzUgNzI3LjkyNCwyNzQuMUM3MjYuNzU4LDI3Mi44NSA3MjUuMjU4LDI3Mi4yMjUgNzIzLjQyNCwyNzIuMjI1QzcyMS41OTEsMjcyLjIyNSA3MjAuMDQ5LDI3Mi44NSA3MTguNzk5LDI3NC4xQzcxNy41NDksMjc1LjM1IDcxNi45MjQsMjc2Ljg5MiA3MTYuOTI0LDI3OC43MjVMNzE2LjkyNCwzODAuNDc1QzcxNi45MjQsMzgyLjE0MiA3MTcuNTQ5LDM4My42IDcxOC43OTksMzg0Ljg1QzcyMC4wNDksMzg2LjEgNzIxLjU5MSwzODYuNzI1IDcyMy40MjQsMzg2LjcyNVoiIHN0eWxlPSJmaWxsOnJnYigzOCwxNTcsMjU1KTtmaWxsLXJ1bGU6bm9uemVybzsiLz4KICAgICAgICAgICAgPC9nPgogICAgICAgICAgICA8ZyB0cmFuc2Zvcm09Im1hdHJpeCgxLDAsMCwxLC0zMy42NzQ1LC0xNjYuNTY0KSI+CiAgICAgICAgICAgICAgICA8cGF0aCBkPSJNNzc5LjkyNCw0MTcuMjI1TDc3OS45MjQsMjQyLjIyNUw4MTcuMTc0LDI0Mi4yMjVMODE3LjE3NCwzODQuOTc1TDg0OS45MjQsMzg0Ljk3NUw4NDkuOTI0LDQxNy4yMjVMNzc5LjkyNCw0MTcuMjI1WiIgc3R5bGU9ImZpbGw6cmdiKDM4LDE1NywyNTUpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgICAgIDxnIHRyYW5zZm9ybT0ibWF0cml4KDEsMCwwLDEsLTMzLjY3NDUsLTE2Ni41NjQpIj4KICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik05MDMuNjc0LDQxOS43MjVDODk1LjY3NCw0MTkuNzI1IDg4OC4zNDEsNDE3Ljc2NyA4ODEuNjc0LDQxMy44NUM4NzUuMDA4LDQwOS45MzQgODY5LjcxNiw0MDQuNjg0IDg2NS43OTksMzk4LjFDODYxLjg4MywzOTEuNTE3IDg1OS45MjQsMzg0LjE0MiA4NTkuOTI0LDM3NS45NzVMODU5LjkyNCwyODMuNDc1Qzg1OS45MjQsMjc1LjMwOSA4NjEuODgzLDI2Ny45MzQgODY1Ljc5OSwyNjEuMzVDODY5LjcxNiwyNTQuNzY3IDg3NS4wMDgsMjQ5LjUxNyA4ODEuNjc0LDI0NS42Qzg4OC4zNDEsMjQxLjY4NCA4OTUuNjc0LDIzOS43MjUgOTAzLjY3NCwyMzkuNzI1QzkxMS42NzQsMjM5LjcyNSA5MTkuMDA4LDI0MS42ODQgOTI1LjY3NCwyNDUuNkM5MzIuMzQxLDI0OS41MTcgOTM3LjYzMywyNTQuNzY3IDk0MS41NDksMjYxLjM1Qzk0NS40NjYsMjY3LjkzNCA5NDcuNDI0LDI3NS4zMDkgOTQ3LjQyNCwyODMuNDc1TDk0Ny40MjQsMzc1Ljk3NUM5NDcuNDI0LDM4NC4xNDIgOTQ1LjQ2NiwzOTEuNTE3IDk0MS41NDksMzk4LjFDOTM3LjYzMyw0MDQuNjg0IDkzMi4zNDEsNDA5LjkzNCA5MjUuNjc0LDQxMy44NUM5MTkuMDA4LDQxNy43NjcgOTExLjY3NCw0MTkuNzI1IDkwMy42NzQsNDE5LjcyNVpNOTAzLjY3NCwzODYuNzI1QzkwNS41MDgsMzg2LjcyNSA5MDcuMDA4LDM4Ni4xIDkwOC4xNzQsMzg0Ljg1QzkwOS4zNDEsMzgzLjYgOTA5LjkyNCwzODIuMTQyIDkwOS45MjQsMzgwLjQ3NUw5MDkuOTI0LDI3OC43MjVDOTA5LjkyNCwyNzYuODkyIDkwOS4zNDEsMjc1LjM1IDkwOC4xNzQsMjc0LjFDOTA3LjAwOCwyNzIuODUgOTA1LjUwOCwyNzIuMjI1IDkwMy42NzQsMjcyLjIyNUM5MDEuODQxLDI3Mi4yMjUgOTAwLjI5OSwyNzIuODUgODk5LjA0OSwyNzQuMUM4OTcuNzk5LDI3NS4zNSA4OTcuMTc0LDI3Ni44OTIgODk3LjE3NCwyNzguNzI1TDg5Ny4xNzQsMzgwLjQ3NUM4OTcuMTc0LDM4Mi4xNDIgODk3Ljc5OSwzODMuNiA4OTkuMDQ5LDM4NC44NUM5MDAuMjk5LDM4Ni4xIDkwMS44NDEsMzg2LjcyNSA5MDMuNjc0LDM4Ni43MjVaIiBzdHlsZT0iZmlsbDpyZ2IoMzgsMTU3LDI1NSk7ZmlsbC1ydWxlOm5vbnplcm87Ii8+CiAgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgPGcgdHJhbnNmb3JtPSJtYXRyaXgoMSwwLDAsMSwtMzMuNjc0NSwtMTY2LjU2NCkiPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTk5Ny40MjQsNDE3LjIyNUw5NjAuMTc0LDQxNy4yMjVMOTYwLjE3NCwyNDIuMjI1TDk5OC40MjQsMjQyLjIyNUMxMDA3LjU5LDI0Mi4yMjUgMTAxNS44OCwyNDQuMzUgMTAyMy4zLDI0OC42QzEwMzAuNzIsMjUyLjg1IDEwMzYuNjMsMjU4LjU1OSAxMDQxLjA1LDI2NS43MjVDMTA0NS40NywyNzIuODkyIDEwNDcuNjcsMjgxLjA1OSAxMDQ3LjY3LDI5MC4yMjVDMTA0Ny42NywzMDAuMzkyIDEwNDQuOTcsMzA4LjQ3NSAxMDM5LjU1LDMxNC40NzVDMTAzNC4xMywzMjAuNDc1IDEwMjcuNzYsMzI0LjY0MiAxMDIwLjQyLDMyNi45NzVMMTAyMC40MiwzMzAuNzI1QzEwMjcuNzYsMzMxLjg5MiAxMDMzLjU1LDMzNS4yNjcgMTAzNy44LDM0MC44NUMxMDQyLjA1LDM0Ni40MzQgMTA0NC4xNywzNTMuNjQyIDEwNDQuMTcsMzYyLjQ3NUwxMDQ0LjE3LDQxNy40NzVMMTAwNy4xNyw0MTcuNDc1TDEwMDcuMTcsMzYyLjQ3NUMxMDA3LjE3LDM2MC42NDIgMTAwNi41MSwzNTkuMSAxMDA1LjE3LDM1Ny44NUMxMDAzLjg0LDM1Ni42IDEwMDIuMjYsMzU1Ljk3NSAxMDAwLjQyLDM1NS45NzVMOTk3LjQyNCwzNTUuOTc1TDk5Ny40MjQsNDE3LjIyNVpNOTk3LjQyNCwzMDUuOTc1TDEwMDIuMTcsMzA1Ljk3NUMxMDA0LjUxLDMwNS45NzUgMTAwNi40NywzMDUuMTg0IDEwMDguMDUsMzAzLjZDMTAwOS42MywzMDIuMDE3IDEwMTAuNDIsMzAwLjE0MiAxMDEwLjQyLDI5Ny45NzVMMTAxMC40MiwyODMuNDc1QzEwMTAuNDIsMjgxLjE0MiAxMDA5LjYzLDI3OS4xODQgMTAwOC4wNSwyNzcuNkMxMDA2LjQ3LDI3Ni4wMTcgMTAwNC41MSwyNzUuMjI1IDEwMDIuMTcsMjc1LjIyNUw5OTcuNDI0LDI3NS4yMjVMOTk3LjQyNCwzMDUuOTc1WiIgc3R5bGU9ImZpbGw6cmdiKDM4LDE1NywyNTUpO2ZpbGwtcnVsZTpub256ZXJvOyIvPgogICAgICAgICAgICA8L2c+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K"/>
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
        <div class="section center-text">
            <span class="small-text">{{section-3}}</span>
        </div>
        <div class="hidden-small"></div>
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

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Playfair+Display:wght@400;700&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">

<div style="width: 100%; max-width: 700px; margin: 20px auto; background-color: #cdf1de; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 16px rgba(38, 157, 255, 0.3);">

    <div style="width: 100%; font-size: 0; line-height: 0; white-space: nowrap;">
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #8f7fff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #ffea05;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #f01b00;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #269dff;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #dfdfdf;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #03bb5c;"></div>
        <div style="display: inline-block; width: 14.28%; height: 10px; background-color: #b06800;"></div>
    </div>

    <div style="padding: 40px 20px; text-align: center;">
        <img src="https://emoticolor.org/cdn/emoticolor-logo-small.png"
             alt="Emoticolor logo"
             style="width: 250px; max-width: 80%; height: auto; border: 0;">
    </div>

    <div style="background-color: #269dff; padding: 20px; text-align: center;">
        <h1 style="margin: 0; color: #ffffff; font-family: 'Inter', sans-serif; font-size: 24px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
            {{section-1}}
        </h1>
    </div>

    <div style="background-color: #ffffff; padding: 30px; color: #269dff; font-family: 'Playfair Display', sans-serif; font-size: 18px; line-height: 1.6;">
        Hello <b style="color: #269dff;">{{username}}</b>,<br><br>
        {{section-2}}
    </div>

    <div style="background-color: #03bb5c; padding: 25px; text-align: center;">
            <span style="font-family: 'JetBrains Mono', monospace; font-size: 36px; font-weight: bold; color: #ffffff; letter-spacing: 5px;">
                {{code}}
            </span>
    </div>

    <div style="background-color: #cdf1de; padding: 15px; text-align: center; color: #269dff; font-family: 'Playfair Display', sans-serif; font-size: 13px;">
        {{section-3}}
    </div>

    <div style="background-color: #ffffff; padding: 30px; border-bottom: 4px solid #269dff; color: #269dff; font-family: 'Playfair Display', Georgia, serif; font-size: 18px;">
        Best regards,<br>
        <span style="font-weight: bold; font-size: 20px;">Sav</span>
    </div>

    <div style="padding: 20px; text-align: center; color: #269dff; font-family: 'Inter', sans-serif; font-size: 12px;">
        Request received from <b style="color: #269dff;">{{ip-address}}</b>
    </div>
</div>

</body>
</html>