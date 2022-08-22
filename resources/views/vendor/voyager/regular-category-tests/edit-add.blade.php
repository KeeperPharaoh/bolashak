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
        Vue.config.devtools = true;
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
    .loader-wrapper{
        position: fixed;
        width:100%;
        height:100%;
        top:50%;
        left:50%;
        transform:translate(-50%, -50%);
        background-color:rgba(0,0,0, 0.7);
        display:flex;
        align-items: center;
        justify-content:center;
    }
    .lds-facebook {
  display: inline-block;
  position: relative;
  /* width: 180px;
  height: 180px; */

}
.lds-facebook div {
  display: inline-block;
  position: absolute;
  left: 8px;
  width: 16px;
  background: #fff;
  animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
}
.lds-facebook div:nth-child(1) {
  left: 8px;
  animation-delay: -0.24s;
}
.lds-facebook div:nth-child(2) {
  left: 32px;
  animation-delay: -0.12s;
}
.lds-facebook div:nth-child(3) {
  left: 56px;
  animation-delay: 0;
}
@keyframes lds-facebook {
  0% {
    top: 8px;
    height: 64px;
  }
  50%, 100% {
    top: 24px;
    height: 32px;
  }
}

.questoins-wrapper{
    margin-bottom:16px;
    border:2px solid #c4c4c4;
    padding:8px;
}

.answers-wrapper .input-group{
    padding:8px;
    margin-bottom:8px;
    border:1px solid #c7c7c7;
}
.one-answer{}
.is-updated{
    border:2px solid green;
}
</style>
