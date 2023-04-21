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

    @include("errors::style")

</head>
<body>
    <div class="container stack" id="stack">
        <div class="block block-content caption">
            <div class="type">{{$exception}}</div>
            <div class="version"><span>PHP <b>{{$php}}</b></span><span>Imhotep <b>{{$imhotep}}</b></span></div>
            <div class="message">{{$message}}</div>
            <div class="details">Details</div>
        </div>

        @verbatim
        <div class="block">
            <div class="row">
                <div class="col-4 trace-list">
                    <ul>
                        <li v-for="(item, index) in menuItems" :key="index" @click="setIndex(index)">
                            <div>{{item.name}}: {{item.line}}</div>
                            <div><b>{{item.function}}</b></div>
                        </li>
                    </ul>
                </div>
                <div class="col-8">
                    <div class="trace-code">
                        <div v-for="item in code" class="line" :class="{'active': line==item.line}">
                            <span class="num">{{item.line}}</span>
                            <pre><code class="language-php">{{item.code}}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endverbatim
    </div>

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
                }
            },
            mounted(){
                this.hljs();
            }
        }).mount('#stack');
    </script>
</body>
</html>