import { MigrosAPI } from 'migros-api-wrapper'
import 'dotenv/config'
import axios from 'axios'

const productId = '100119063'
const costCenterUrl = 'https://pokeapi.freakpants.ch/api/outdated-cost-centers'
const updateStockUrl = 'https://pokeapi.freakpants.ch/api/update-stock'

const guestInfo = await MigrosAPI.account.oauth2.getGuestToken()

let requestCount = 0
const startTime = Date.now()

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))

while (true) {
  const elapsed = (Date.now() - startTime) / 1000
  if (elapsed > 50) break

  try {
    // Fetch cost centers
    const costCenterResponse = await axios.get(costCenterUrl)
    const costCenterData = costCenterResponse.data
    console.log(`[${elapsed.toFixed(1)}s] Cost center IDs:`, costCenterData)

    // Get stock info
    const productSupplyOptions = {
      pids: productId,
      costCenterIds: costCenterData,
    }

    const response = await MigrosAPI.products.productStock.getProductSupply(
      productSupplyOptions,
      { leshopch: guestInfo.token },
    )

    console.log(`[${elapsed.toFixed(1)}s] 🔁 Response #${requestCount + 1}:`, response)

    // Post to update endpoint
    const updateStockResponse = await axios.post(updateStockUrl, response)
    console.log(`[${elapsed.toFixed(1)}s] ✅ Update result:`, updateStockResponse.data)

    requestCount++
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const data = error.response?.data
      console.error('❌ Error:', data?.error || error.message)
      console.error('📋 Details:', data?.details || 'No details')
    } else {
      console.error('❌ Unexpected error:', error.message || error)
    }
  }

  // Random delay between 860ms and 1800ms
  const delay = 860 + Math.random() * (1800 - 860)
  console.log(`⏱️ Waiting ${Math.round(delay)}ms before next request...`)
  await wait(delay)
}

const totalTime = ((Date.now() - startTime) / 1000).toFixed(1)
console.log(`🛑 Done after ${totalTime}s with ${requestCount} requests.`)
process.exit(0)