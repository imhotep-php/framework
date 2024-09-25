<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$message}}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <style>
        html {
            font-size: 16px;
            line-height: 1.4;
        }

        .container {
            width: 960px;
            padding: 0 20px;
        }

        .header {
            padding: 20px 0;
            background: #eab9b9;
            margin: 0 0 20px 0;
        }
        .header h1 {
            font-size: 140%;
            margin: 0 0 10px 0;
            padding: 0;
        }
        .header p {
            margin: 0;
        }

        .block {
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 20px;
            margin: 0 0 20px 0;
        }

        .stack {
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }
        .stack .item {
            position: relative;
            padding: 10px 20px;
        }
        .stack .item .title {
            cursor: pointer;
        }
        .stack .item .title .function {
            color: #55f;
        }
        .stack .item .title .function:before {
            content: "›";
            margin: 0 5px;
            color: rgba(0, 0, 0, 0.3);
        }
        .stack .item .badge {
            position: absolute;
            right: 15px;
            top: 13px;
            color: #ffffff;
            font-weight: normal;
        }
        .stack .item + .item {
            border-top: 1px solid #dee2e6;
        }
        .stack .code {
            display: none;
            width: 100%;
            overflow: hidden;
            overflow-x: auto;
            padding: 10px 0;
            margin-top: 10px;
            background: #f3f3f3;
        }
        .stack .item.opened .code {
            display: block;
        }
        .stack .code .line {
            white-space: nowrap;
            padding: 0 20px;
        }
        .stack .code .line .num {
            display: inline-block;
            width: 36px;
            opacity: 0.4;
        }
        .stack .code .line > span {
            vertical-align: top;
            font-size: 80%;
        }
        .stack .code .line > pre {
            display: inline-block;
            margin: 0;
            vertical-align: top;
            font-size: 80%;
        }
        .stack .code .line.active {
            background: #ffd8d8;
        }
    </style>

    <style>
        .topbar {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            font-size: 80%;
            line-height: 2;
        }
        .topbar .menu ul{
            display: block;
            list-style: none;
            margin: 0; padding: 0;
        }
        .topbar .menu ul li{
            display: inline-block;
            text-transform: uppercase;
            margin: 0; padding: 0;
            color: #666666;
        }
        .topbar .menu ul li + li{
            margin-left: 20px;
        }
        .topbar .menu ul li:hover{
            color: #000000;
            cursor: pointer;
        }
        .topbar .menu ul li.active{
            font-weight: bold;
            color: #000000;
        }

        .version {
            float: right;
        }

        /*
        .version {
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            font-size: 80%;
            line-height: 2;
        }
        .version span + span {
            margin-left: 30px;
        }
        */

    </style>
</head>
<body>
    <div class="topbar">
        <div class="container">
            <div class="version">
                <span>PHP <b>{{$version['php']}}</b></span>
                <span>Imhotep <b>{{$version['imhotep']}}</b></span>
            </div>
            <div class="menu">
                <ul>
                    <li class="active">Stack</li>
                    <li>Context</li>
                    <li>Container</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="header">
        <div class="container">
            <h1>{{$exception}}</h1>
            <p>{{$message}}</p>
        </div>
    </div>

    @verbatim
    <div class="container">
        <div id="stack" class="stack mb-4">
            <div class="item" v-for="(item, index) in menuItems" :key="index" @click="setIndex(index)">
                <div class="title" @click="toggle($event.target)">
                    <span class="name">{{item.name}}</span>
                    <span class="function">{{item.function}}</span>
                    <span class="args">({{item.args}})</span>
                </div>
                <span class="badge bg-secondary" title="line">{{item.line}}</span>
                <div class="code">
                    <div v-for="code in item.code" class="line" :class="{'active': code.line==item.line}">
                        <span class="num">{{code.line}}</span>
                        <pre><code class="language-php">{{code.code}}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endverbatim

    <script src="https://code.jquery.com/jquery-3.7.0.slim.min.js" integrity="sha256-tG5mcZUtJsZvyKAxYLVXrmjKBVLd6VpVccqz/r4ypFE=" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.7.0/build/highlight.min.js"></script>
    <script type="text/javascript">
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    index: 0,
                    trace: {!! $trace !!}
                }
            },
            computed: {
                menuItems() {
                    let result = [];
                    for (let key in this.trace) {
                        let trace = this.trace[key];

                        result.push({
                            'name': (trace.class !== '') ? trace.class : trace.file,
                            'line': trace.line,
                            'function': trace.function,
                            'code': trace.code,
                            'args': trace.args.join(', '),
                        });
                    }
                    return result;
                },
                code() {
                    return this.trace[this.index].code;
                },
                line() {
                    return this.trace[this.index].line;
                }
            },
            methods: {
                async setIndex(index) {
                    this.index = index;
                    await this.$nextTick();
                    this.hljs();
                },
                hljs(){
                    document.querySelectorAll('code').forEach(el => {
                        hljs.highlightElement(el);
                    });
                },
                toggle(el) {
                    let isOpened = $(el).parent().hasClass('opened');

                    $('.stack > .opened').removeClass('opened');

                    if (isOpened === false) {
                        $(el).parent().addClass('opened');
                    }
                }
            },
            mounted(){
                this.hljs();
            }
        }).mount('#stack');
    </script>


    <div class="container">
        <div class="block">
            <h2>Aliases</h2>

            <table>
                <tr>
                    <th style="padding: 5px 0;">Alias</th>
                    <th style="padding: 5px 10px;"></th>
                    <th style="padding: 5px 0;">Abstract</th>
                </tr>
                @foreach($container['aliases'] as $alias => $abstract)
                    <tr>
                        <td style="padding: 5px 0;">{{ $alias  }}</td>
                        <td style="padding: 5px 10px; color: #999;">›</td>
                        <td style="padding: 5px 0;">{{ $abstract  }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="block">
            <h2>Bindings</h2>

            <table>
                <tr>
                    <th style="padding: 5px 20px 5px 0;">Abstract</th>
                    <th style="padding: 5px 20px 5px 0;">Concrete</th>
                    <th style="padding: 5px 20px 5px 0;">Shared</th>
                </tr>
                @foreach($container['bindings'] as $abstract => $value)
                    <tr>
                        <td style="padding: 5px 20px 5px 0;">{{ $abstract  }}</td>
                        <td style="padding: 5px 20px 5px 0;">{{ $value['concrete']  }}</td>
                        <td style="padding: 5px 20px 5px 0;">{{ $value['shared']  }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="block">
            <h2>Instances</h2>

            <table>
                <tr>
                    <th style="padding: 5px 0;">Abstract</th>
                    <th style="padding: 5px 10px;"></th>
                    <th style="padding: 5px 0;">Concrete</th>
                </tr>
                @foreach($container['instances'] as $abstract => $concrete)
                    <tr>
                        <td style="padding: 5px 0;">{{ $abstract  }}</td>
                        <td style="padding: 5px 10px; color: #999;">›</td>
                        <td style="padding: 5px 0;">{{ $concrete  }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</body>
</html>