export default {
    template: `
    <form @submit.prevent="save" style="transition:0.5s">
        <div class="form-group">
            <label for="exampleFormControlSelect1">Название</label>
            <input  v-model="title" type="text" class="form-control"  placeholder="Название">
        </div>
        <div class="form-group">
            <label for="exampleFormControlSelect1">Название</label>
            <textarea  v-model="instruction" type="text" class="form-control"  placeholder="инструкция"></textarea>
        </div>
        <div class="form-group">
            <label for="exampleFormControlSelect1">Выберите язык</label>
            <select v-model="language" class="form-control form-control-lg">
                <option :selected="language=='ru'" value="ru">Русский</option>
                <option :selected="language=='kz'" value="kz">Казахский</option>
            </select>
        </div>
        <div class="form-group">
            <h4 class="btn-primary" style="padding:8px" disabled>Вопросы</h4>
        </div>

        <button @click.prevent.stop="addQuestion" class="btn btn-success btn-add-question " style="justify-self:flex-end"><span class="glyphicon glyphicon-plus"></span></button>
        <div v-for="(question, questionIndex) in test"
             :key="question.uuid"
              class="questoins-wrapper"
             >

            <div class="input-group">
                <input @input="setQuestion($event, questionIndex)" :value="question.question" type="text" class="form-control"  placeholder="вопрос">
                <span class="input-group-btn">
                     <button :disabled="isLoading" @click.prevent.stop="deleteQuestion(questionIndex)" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></button>
                </span>
            </div>
            <div class="input-group">
                <input @input="setQuestionRawImage($event, questionIndex)" accept="image/*" :value="question.rawImage" type="file" class="form-control"  placeholder="вопрос">
                <span class="input-group-btn">
                     <button :disabled="isLoading" @click.prevent.stop="deleteQuestionImage(questionIndex)" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span></button>
                </span>
            </div>
             <img v-if="question.image" :src="question.image"/>
            <div class="form-group">
                <label for="exampleFormControlSelect1">Тип вопроса</label>
                <select @change="selectQuestionType($event, questionIndex)" class="form-control form-control-lg">
                    <option :selected="question.type === 'coommon'" :value="question.type || 'common'">Один ответ</option>
                    <option :selected="question.type === 'multiple_choice'" :value="question.type ||'multiple_choice'">Несколько ответов</option>
                </select>
            </div>

            <div class="answers-wrapper" style="margin-left:32px;">

                <div v-for="(answer, answerIndex) in question.answers" :key="answer.uuid" class="input-group" style="margin-bottom:4px; flex-direction:column; ">
                    <div class="input-group" style="width:100%;">
                       <span v-if="!question.common" class="input-group-addon">
                        <input
                                :checked="answer.right"
                                @change="setAnswerRight($event, questionIndex, answerIndex)"
                                type="checkbox">
                    </span>
                    <span v-else class="input-group-addon">
                        <input :checked="answer.right" :name="getRadioInputName(question)"   @change="setAnswerRight($event, questionIndex, answerIndex)" type="radio" aria-label="..." >
                    </span>

                    <input :value="answer.title" @input="setAnswerTitle($event, questionIndex, answerIndex)" type="text" class="form-control" aria-label="..." placeholder="ответ">
                    <span class="input-group-btn">
                        <button :disabled="isLoading" @click.prevent.stop="deleteAnswer(questionIndex, answerIndex)" class="btn btn-danger">
                            <span class="glyphicon glyphicon-trash"></span>
                        </button>
                    </span>
                    </div>
                    <div class="input-group">
                        <input @input="setAnswerRawImage($event, questionIndex, answerIndex)" accept="image/*"  type="file" class="form-control"  placeholder="вопрос">
                    </div>
                    <img v-if="answer.image" :src="answer.image"/>
                </div>
            </div>
            <div v-if="hasQuestion" class="form-group" style="margin-left:32px;">
                <button @click.prevent.stop="addAnswer(questionIndex)" class="btn btn-success" style="justify-self:flex-end">Добавить ответ </button>
            </div>
        </div>

        <button class="btn btn-primary" type="submit" :disabled="isLoading">Сохранить</button>
        <p v-if="successMessage" class="alert alert-success" v-html="successMessage"> </p>
        <p v-if="errorMessage" class="alert alert-warn" v-html="errorMessage">  </p>

    </form>`,

    data() {
        return {
            language: "ru",
            title: "",
            instruction: "",
            test: [],
            successMessage: "",
            errorMessage: "",
            isLoading: false,
            isUpdate: false,
            id: null,
        };
    },
    computed: {
        hasQuestion() {
            return this.test?.length;
        },
        baseUrl() {
            return "https://api.ok-bolashak.kz";
        },
    },

    created() {
        const path = window.location.pathname;
        const pathes = path?.split("/").filter((p) => p !== "");
        if (pathes?.length >= 3 && pathes[2] !== "create") {
            this.getTest(pathes[2]);
            this.id = pathes[2];
            this.isUpdate = true;
        }
    },
    watch: {
        test: {
            deep: true,
            handler() {
                console.log(this.test);
            },
        },
    },
    methods: {
        getRadioInputName(question) {
            return "commonAnswer" + question.id ? question.id : question.uuid;
        },
        getTest(id) {
            axios
                .get(this.baseUrl + "/api/admin/test/" + id)
                .then((response) => {
                    this.test = response.data.questions.map((q) => {
                        q.common = q.type === "common";
                        q.answers.forEach((el) => {
                            el.title = el.answer;
                            // el.right = true;
                        });
                        return q;
                    });
                    this.title = response.data?.title;
                    this.id = response.data?.id;
                    this.language = response.data?.language || "ru";
                });
        },
        addQuestion() {
            const question = {
                uuid: Math.random() + new Date().getTime(),
                question: "",
                common: true,
                image: null,
                rawImage: "",
                answers: [],
            };
            this.test.push(question);
        },

        deleteQuestion(questionIndex) {
            if (this.isUpdate) {
                this.isLoading = true;
                axios
                    .delete(this.baseUrl + "/api/admin/test", {
                        data: {
                            test_id: this.id,
                            questionId: this.test[questionIndex].id,
                        },
                    })
                    .then((response) => {
                        this.isLoading = false;
                        this.test.splice(questionIndex, 1);
                        console.log(response);
                    })
                    .catch((e) => {
                        this.isLoading = false;
                        console.log(e);
                    });
                return;
            }
            this.test.splice(questionIndex, 1);
        },

        addAnswer(questionIndex) {
            const answer = {
                uuid: Math.random() + new Date().getTime(),
                title: "",
                right: false,
                image: "",
            };
            this.test[questionIndex].answers.push(answer);
        },

        deleteAnswer(questionIndex, answerIndex) {
            const answer = this.test[questionIndex].answers[answerIndex].id;
            if (this.isUpdate && answer) {
                this.isLoading = true;
                axios
                    .delete(this.baseUrl + "/api/admin/test", {
                        data: {
                            test_id: this.id,
                            questionId: this.test[questionIndex].id,
                            answerId: answer,
                        },
                    })
                    .then((response) => {
                        this.isLoading = false;
                        this.test[questionIndex].answers.splice(answerIndex, 1);
                        console.log(response);
                    })
                    .catch((e) => {
                        this.isLoading = false;
                        console.log(e);
                    });
                return;
            }
            this.test[questionIndex].answers.splice(answerIndex, 1);
        },

        selectQuestionType(event, questionIndex) {
            this.test[questionIndex].common = event.target.value === "common";
            console.log(event, questionIndex, this.test[questionIndex]);
        },
        setQuestion({ target }, questionIndex) {
            this.test[questionIndex].question = target.value;
        },
        setQuestionRawImage({ target }, questionIndex) {
            const fileReader = new FileReader();
            fileReader.onload = () => {
                const base64 = fileReader.result;

                this.test[questionIndex].image = base64;
            };

            fileReader.readAsDataURL(target.files[0]);
        },
        setAnswerRawImage({ target }, questionIndex, answerIndex) {
            const fileReader = new FileReader();
            fileReader.onload = () => {
                const base64 = fileReader.result;

                this.test[questionIndex].answers[answerIndex].image = base64;
            };

            fileReader.readAsDataURL(target.files[0]);
        },
        setAnswerTitle({ target }, questionIndex, answerIndex) {
            this.test[questionIndex].answers[answerIndex].title = target.value;
        },
        setAnswerRight({ target }, questionIndex, answerIndex) {
            const questionType = this.test[questionIndex].common;
            if (questionType) {
                this.test[questionIndex].answers.forEach((a) => {
                    a.right = false;
                });
                this.test[questionIndex].answers[answerIndex].right =
                    target.checked;
                return;
            }
            this.test[questionIndex].answers[answerIndex].right =
                target.checked;
        },

        isRightAnswer(questionIndex, answerIndex) {
            return this.test[questionIndex].answers[answerIndex].right;
        },

        save() {
            this.isLoading = true;

            if (!this.isUpdate) {
                axios
                    .post(this.baseUrl + "/api/admin/test", {
                        title: this.title,
                        language: this.language,
                        test: this.test,
                        instruction: this.instruction,
                    })
                    .then((response) => {
                        this.requestSuccess("create", response.data?.message);
                    })
                    .catch((e) => {
                        this.requestFailure(e);
                    });
                return;
            }
            axios
                .post(`${this.baseUrl}/api/admin/test/${this.id}`, {
                    title: this.title,
                    language: this.language,
                    test: this.test,
                })
                .then((response) => {
                    this.requestSuccess("update", response.data?.message);
                })
                .catch((e) => {
                    this.requestFailure(e);
                });
        },
        requestSuccess(requestType, message) {
            if (requestType === "update") {
                this.isLoading = false;
                this.successMessage = message || "Успешно";
                setTimeout(() => {
                    this.successMessage = "";
                }, 3000);
                return;
            }
            this.isLoading = false;
            this.successMessage = message || "Успешно";
            this.test = [];
            this.title = "";
            this.language = "ru";
            setTimeout(() => {
                this.successMessage = "";
            }, 3000);
        },
        requestFailure(e) {
            this.isLoading = false;
            this.errorMessage = e;
            setTimeout(() => {
                this.errorMessage = "";
            }, 3000);
        },
    },
};
