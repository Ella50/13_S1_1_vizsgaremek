<template>
  <div class="m-5">
    <h2>Player Registration</h2>
    <form @submit.prevent="save">
      <div class="form-group row">
        <label>Player Name:</label>
        <input type="text" v-model="player.name" class="form-control mt-2" placeholder="Name" id="nameInput">
        <div v-if="errors.name" class="text-danger" id="nameError">{{ errors.name }}</div>
      </div>
      <div class="form-group row mt-5">
        <label>Player Age:</label>
        <input type="number" v-model="player.age" class="form-control mt-2" placeholder="Age (Number)" id="ageInput">
        <div v-if="errors.age" class="text-danger" id="ageError">{{ errors.age }}</div>
      </div>
      <div class="form-group row mt-5">
        <label>Player Points:</label>
        <input type="text" v-model="player.points" class="form-control mt-2" placeholder="Player Points" readonly>
      </div>
      <div class="form-group row mt-5">
        <label>Player Address:</label>
        <input type="text" v-model="player.address" class="form-control mt-2" placeholder="Player Address" id="addressInput">
        <div v-if="errors.address" class="text-danger" id="addressError">{{ errors.address }}</div>
      </div>
      <button type="submit" class="btn btn-primary mt-3 ms-3">Save</button>
      <router-link to="/" tag="button" class="btn btn-primary mt-3 ms-3">Cancel</router-link>
    </form>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      player: {
        id: '',
        name: '',
        age: null,
        points: 0,
        address: '',
      },
      errors: {},
    };
  },
  methods: {
    save() {
      if (!this.validateForm()) {
        return; // Stop the execution if the form is not valid
      }
      axios.post(`http://127.0.0.1:8000/api/players/`, this.player)
        .then(({ data }) => {
          alert("Player Added");
          this.player.name = '';
          this.player.age = null;
          this.player.points = 0;
          this.player.address = '';
          this.id = '';
          this.$router.push("/");
        })
        .catch((error) => {
          alert(error.message || error.response.data.message);
        });
    },
    validateForm() {
      let isValid = true;
      if (!this.player.name || this.player.name.length < 3 || this.player.name.length > 20) {
        this.errors.name = 'Name must be between 3 and 20 characters';
        isValid = false;
      } else {
        this.errors.name = '';
      }
      if (this.player.age <= 3) {
        this.errors.age = 'Age must be valid (Greater than 2)';
        isValid = false;
      } else {
        this.errors.age = '';
      }
      if (!this.player.address) {
        this.errors.address = 'Address is required';
        isValid = false;
      } else {
        this.errors.address = '';
      }
      return isValid;
    },
  },
};
</script>