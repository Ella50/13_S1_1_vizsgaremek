<template>
  <div>
    <div class="m-5">
      <div class="card">
        <div class="card-header">
          <h1 class="text-center fw-bolder">LEADERBOARD</h1>
          <div class="row">
            <div class="container">
              <div class="row height d-flex justify-content-start">
                <div class="col-md-8">
                  <div class="search">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" v-model="searchQuery" placeholder="Search player by name!!!">
                    <button type="button" class="btn btn-primary btn-sm mt-3 ms-3" @click="filterByName">Search</button>
                    <button type="button" class="btn btn-primary btn-sm mt-3 ms-3" @click="removeFilter" v-if="filteredQuery.length > 0">Clear Filter - {{ filteredQuery }}</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <PlayerTable
            :filteredPlayers="filteredPlayers"
            :searchQuery="searchQuery"
            :showDetails="showDetails"
            @remove-player="removePlayer"
            @add-points="addPoints"
            @subtract-points="subtractPoints"
            @toggle-details="toggleDetails"
            @sort-by-name="sortByName"
            @sort-by-points="sortByPoints"
          />
        </div>
        <div class="card-footer">
          <router-link to="/registrationForm" tag="button" class="btn btn-dark float-end">+ Add Player</router-link>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios';
import PlayerTable from '../components/PlayerTable.vue';

export default {
  name: 'Player',
  components: {
    PlayerTable
  },
  data() {
    return {
      allPlayers: [],
      filteredPlayers: [],
      showDetails: [],
      searchQuery: '',
      filteredQuery: '',
    };
  },
  created() {
    this.playerLoad();
  },
  methods: {
    filterByName() {
      this.filteredQuery = this.searchQuery;
      if (this.searchQuery.length > 0) {
        const query = this.searchQuery.toLowerCase();
        this.filteredPlayers = this.allPlayers.filter((user) =>
          user.name.toLowerCase().includes(query)
        );
      } else {
        this.filteredPlayers = this.allPlayers;
      }
    },
    highlightSearchQuery(name) {
      const query = this.searchQuery.toLowerCase();
      const index = name.toLowerCase().indexOf(query);
      if (index > -1) {
        const highlighted = name.substring(0, index) +
          '<mark>' + name.substring(index, index + query.length) + '</mark>' +
          name.substring(index + query.length);
        return highlighted;
      }
      return name;
    },
    playerLoad() {
      var page = "http://127.0.0.1:8000/api/players";
      axios.get(page)
        .then(({ data }) => {
          console.log(data);
          this.allPlayers = data;
          this.filteredPlayers = this.allPlayers;
          this.sortByPoints();
        })
        .catch((error) => {
          alert(error.message || error.response.data.message);
        });
    },
    removeFilter() {
      this.searchQuery = '';
      this.filteredQuery = '';
      this.filteredPlayers = this.allPlayers;
    },
    updateData() {
      var editrecords = `http://127.0.0.1:8000/api/players/${this.player.id}`;
      axios.put(editrecords, this.player)
        .then(({ data }) => {
          alert("Player updated");
          this.playerLoad();
          this.sortByPoints();
        })
        .catch((error) => {
          alert(error.message || error.response.data.message);
        });
    },
    removePlayer(player) {
      var url = `http://127.0.0.1:8000/api/players/${player.id}`;
      axios.delete(url);
      alert("Player deleted");
      this.playerLoad();
      this.removeFilter();
    },
    addPoints(player) {
      this.player = player;
      this.player.points += 1;
      this.updateData();
      this.removeFilter();
      this.sortByPoints();
    },
    subtractPoints(player) {
      this.player = player;
      this.player.points -= 1;
      this.updateData();
      this.removeFilter();
      this.sortByPoints();
    },
    toggleDetails(player) {
      if (this.showDetails.includes(player.id)) {
        this.showDetails = this.showDetails.filter(id => id !== player.id);
      } else {
        this.showDetails.push(player.id);
      }
    },
    sortByName() {
      this.allPlayers.sort((a, b) => {
        const nameA = a.name.toLowerCase();
        const nameB = b.name.toLowerCase();

        if (nameA < nameB) {
          return -1;
        } else if (nameA > nameB) {
          return 1;
        } else {
          return 0;
        }
      });
    },
    sortByPoints() {
      this.allPlayers.sort((a, b) => b.points - a.points);
    }
  }
};
</script>
