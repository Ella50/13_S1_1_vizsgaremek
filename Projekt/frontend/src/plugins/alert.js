import { addAlert } from "@/components/auth/AppAlert.vue";

export default {
  install(app) {
    app.config.globalProperties.$alert = addAlert;
  },
};