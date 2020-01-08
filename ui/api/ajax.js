/* eslint-disable no-console */
import axios from 'axios'
import { forEach } from 'lodash'

export function request(params) {
  const form = new FormData()
  form.append('ajax', true)
  form.append('action', params.action)
  form.append('controller', params.controller)
  forEach(params.data, (value, key) => {
    form.append(key, value)
  })

  return (
    axios
      // eslint-disable-next-line no-undef
      .post(ajax_controller_url, form)
      .then(res => res.data)
      .catch(error => {
        console.log(error)
      })
  )
}
