import { MigrosAPI } from 'migros-api-wrapper'
import 'dotenv/config'
import axios from 'axios'

const productIds = ['100119063', '100007250']
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

    for (const productId of productIds) {
      const productSupplyOptions = {
        pids: productId,
        costCenterIds: costCenterData,
      }

      try {
        const response = await MigrosAPI.products.productStock.getProductSupply(
          productSupplyOptions,
          { leshopch: guestInfo.token },
        )

        console.log(`[${((Date.now() - startTime) / 1000).toFixed(1)}s] 🔁 Response #${requestCount + 1} (PID: ${productId}):`, response)

        // Post to update endpoint
        const updateStockResponse = await axios.post(updateStockUrl, response)
        console.log(`[${((Date.now() - startTime) / 1000).toFixed(1)}s] ✅ Update result:`, updateStockResponse.data)

        requestCount++
      } catch (error) {
        if (axios.isAxiosError(error)) {
          const data = error.response?.data
          console.error(`❌ Error for PID ${productId}:`, data?.error || error.message)
          console.error('📋 Details:', data?.details || 'No details')
        } else {
          console.error(`❌ Unexpected error for PID ${productId}:`, error.message || error)
        }
      }

      // Random delay between 860ms and 1800ms
      const delay = 860 + Math.random() * (1800 - 860)
      console.log(`⏱️ Waiting ${Math.round(delay)}ms before next product...`)
      await wait(delay)

      // Check time again in case we need to break early
      if ((Date.now() - startTime) / 1000 > 50) break
    }
  } catch (error) {
    console.error('❌ Failed to get cost centers or run main loop:', error.message || error)
  }
}

const totalTime = ((Date.now() - startTime) / 1000).toFixed(1)
console.log(`🛑 Done after ${totalTime}s with ${requestCount} requests.`)
process.exit(0)
