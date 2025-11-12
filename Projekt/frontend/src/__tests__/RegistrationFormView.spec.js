import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import axios from 'axios'
import RegistrationForm from '../views/RegistrationForm.vue'


describe('RegistrationForm', () => {
    it('displays the "Player Registration" heading correctly', () => {
        const wrapper = mount(RegistrationForm)
        const heading = wrapper.find('h2')
        expect(heading.text()).toBe('Player Registration')
    })

    it('correctly binds form fields to the player data object', async () => {
        const wrapper = mount(RegistrationForm)
        const nameInput = wrapper.find('#nameInput')
        const ageInput = wrapper.find('#ageInput')
        const addressInput = wrapper.find('#addressInput')

        nameInput.element.value = 'John Doe'
        ageInput.element.value = '25'
        addressInput.element.value = '123 Main St'

        nameInput.trigger('input')
        ageInput.trigger('input')
        addressInput.trigger('input')

        expect(wrapper.vm.player.name).toBe('John Doe')
        expect(wrapper.vm.player.age).toBe(25)
        expect(wrapper.vm.player.address).toBe('123 Main St')
    })
})
