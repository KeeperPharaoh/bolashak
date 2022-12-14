export default {
    template: `
    <div v-if="isLoading" class="loader-wrapper">
    <div  class="lds-facebook"><div></div><div></div><div></div></div>

    </div>
    <div v-else class="container">
    <form v-if="!isStarting" @submit.prevent="startCreate">
            <button class="btn btn-success">Начать</button>
    </form>
    <form v-else @submit.prevent="save" style="transition:0.5s">
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

        <button @click.prevent.stop="addQuestion" class="btn btn-success btn-add-question " style="justify-self:flex-end">Добавить вопрос <span class="glyphicon glyphicon-plus"></span> </button>
        <div v-for="(question, questionIndex) in test"
             :key="question.uuid"
              class="questoins-wrapper"
              :class="{'is-updated':question.isUpdated}"
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

            <div class="answers-wrapper"  style="margin-left:32px;">

                <div :class="{'is-updated':answer.isUpdated}" v-for="(answer, answerIndex) in question.answers" :key="answer.uuid" class="input-group" style="margin-bottom:4px; flex-direction:column; ">
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

    </form>
    </div>
    `,

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
            isStarting: false,
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
            this.isStarting = true;
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
        startCreate() {
            this.isLoading = true;
            axios.get("/api/start-creating").then((response) => {
                this.id = response.data.id;
                this.isStarting = true;
                this.isLoading = false;
            });
        },
        getRadioInputName(question) {
            return "commonAnswer" + question.id ? question.id : question.uuid;
        },
        getTest(id) {
            this.isLoading = true;
            axios
                .get(this.baseUrl + "/api/admin/test/" + id)
                .then((response) => {
                    this.test = response.data.questions.map((q) => {
                        q.common = q.type === "common";
                        q.isUpdated = false;

                        q.answers.forEach((el) => {
                            el.title = el.answer;
                            el.isUpdated = false;
                            // el.right = true;
                        });
                        return q;
                    });
                    this.title = response.data?.title;
                    this.id = response.data?.id;
                    this.language = response.data?.language || "ru";
                    this.isLoading = false;
                });
        },
        addQuestion() {
            this.isLoading = true;
            axios.post(`/api/question/${this.id}`).then((response) => {
                const qId = response.data.id;
                const question = {
                    uuid: Math.random() + new Date().getTime(),
                    id: qId,
                    question: "",
                    common: true,
                    image: null,
                    rawImage: "",
                    answers: [],
                    isUpdated: true,
                };
                this.test.push(question);
                this.isLoading = false;
            });
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
            this.isLoading = true;
            const qId = this.test[questionIndex].id;
            axios.post(`/api/answer/${qId}`).then((response) => {
                const answer = {
                    uuid: Math.random() + new Date().getTime(),
                    title: "",
                    right: false,
                    image: "",
                    id: response.data.id,
                    isUpdated: true,
                };
                this.test[questionIndex].answers.push(answer);
                this.isLoading = false;
            });
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
            this.test[questionIndex].isUpdated = true;
            this.test[questionIndex].common = event.target.value === "common";
            console.log(event, questionIndex, this.test[questionIndex]);
        },
        setQuestion({ target }, questionIndex) {
            this.test[questionIndex].question = target.value;
            this.test[questionIndex].isUpdated = true;
        },
        setQuestionRawImage({ target }, questionIndex) {
            this.test[questionIndex].isUpdated = true;
            const fileReader = new FileReader();
            fileReader.onload = () => {
                const base64 = fileReader.result;

                this.test[questionIndex].image = base64;
            };

            fileReader.readAsDataURL(target.files[0]);
        },
        setAnswerRawImage({ target }, questionIndex, answerIndex) {
            this.test[questionIndex].answers[answerIndex].isUpdated = true;
            const fileReader = new FileReader();
            fileReader.onload = () => {
                const base64 = fileReader.result;

                this.test[questionIndex].answers[answerIndex].image = base64;
            };

            fileReader.readAsDataURL(target.files[0]);
        },
        setAnswerTitle({ target }, questionIndex, answerIndex) {
            this.test[questionIndex].answers[answerIndex].isUpdated = true;
            this.test[questionIndex].answers[answerIndex].title = target.value;
        },
        setAnswerRight({ target }, questionIndex, answerIndex) {
            this.test[questionIndex].answers[answerIndex].isUpdated = true;
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
            const questionPromises = [];
            const answersPromises = [];

            this.test.forEach((question) => {
                console.log("QUESTION", question);
                if (question.isUpdated) {
                    const response = axios.post(
                        `/api/question/${question.id}/update`,
                        question
                    );
                    questionPromises.push(response);
                }
                question.answers.forEach((answer) => {
                    if (answer.isUpdated) {
                        const response = axios.post(
                            `/api/answer/${answer.id}/update`,
                            answer
                        );
                        answersPromises.push(response);
                    }
                });
            });
            const allResponses = [...questionPromises, ...answersPromises];
            Promise.all(allResponses).then((response) => {
                this.test.forEach((q) => {
                    q.isUpdated = false;
                });
                console.log(response);
            });

            axios
                .post(`${this.baseUrl}/api/main/${this.id}`, {
                    title: this.title,
                    language: this.language,
                    instruction: this.instruction,
                })
                .then((response) => {
                    this.requestSuccess(response.data?.message);
                })
                .catch((e) => {
                    this.requestFailure(e);
                });
        },
        requestSuccess(message) {
            this.isLoading = false;
            this.successMessage = message || "Успешно";
            setTimeout(() => {
                this.successMessage = "";
                // window.location = `${this.baseUrl}/admin/regular-category-tests`;
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
