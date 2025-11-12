import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import HomeView from '../views/HomeView.vue';

describe('HomeView', () => {
  it('renders leaderboard heading correctly', () => {
    const wrapper = mount(HomeView)
    const heading = wrapper.find('h1.text-center')
    expect(heading.text()).toBe('LEADERBOARD')
  })

  it('filters players by name correctly', async () => {
    const wrapper = mount(HomeView)
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('John')
    const filteredPlayers = wrapper.vm.filteredPlayers
    expect(filteredPlayers.every(player => player.name.toLowerCase().includes('john'))).toBe(true)
  })

  it('clears filter when "Clear Filter" button is clicked', async () => {
    const wrapper = mount(HomeView)
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('John')
    const clearFilterButton = wrapper.find('button.btn-primary.btn-sm.mt-3.ms-3')
    await clearFilterButton.trigger('click')
    const filteredPlayers = wrapper.vm.filteredPlayers
    expect(filteredPlayers.length).toBe(wrapper.vm.allPlayers.length)
  })

  it('sorts players by name correctly', async () => {
    const wrapper = mount(HomeView)
    const sortByNameButton = wrapper.find('button.btn-secondary.btn-sm.ms-3')
    await sortByNameButton.trigger('click')
    const sortedPlayers = wrapper.vm.allPlayers.slice().sort((a, b) => a.name.localeCompare(b.name))
    expect(wrapper.vm.allPlayers).toEqual(sortedPlayers)
  })
})

