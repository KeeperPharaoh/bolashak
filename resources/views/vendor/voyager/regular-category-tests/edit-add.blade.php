@extends('voyager::master')
@section('content')
    <div id="app">

        <div class="container">
            <h1 v-if="!isUpdate" class="main_title"> Создание теста</h1>
            <h1 v-else class="main_title"> Обновление теста</h1>
            <create-form class="create-form" />

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js"></script>
    <script type="module">
        import CreateForm from '/components/create-form.js';

        var app = new Vue({
            el: '#app',
            components: {
                CreateForm
            },
            data() {
                return {
                    isUpdate: false,
                }
            },
            created() {
                const path = window.location.pathname;
                const pathes = path?.split('/').filter(p => p !== '')
                if (pathes?.length >= 3 && pathes[2] !== 'create') {
                    this.isUpdate = true;
                }
            },

        })
    </script>
@endsection

<style>
    .main_title {
        margin: 30px;
    }

    .btn-add-question {
        position: fixed;
        top: 58%;
        right: 2%;
        border-radius: 100%;
        z-index: 999;
    }

    .create-form .input-group {
        /* display: flex; */
    }

    .delete-start {
        transform: translateX(-1000px) transition:2s
    }

</style>
