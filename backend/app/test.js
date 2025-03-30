import { MigrosAPI } from 'migros-api-wrapper';

const guestInfo = await MigrosAPI.account.oauth2.getGuestToken();
const productSupplyOptions = {
    pids: "100024405",
    costCenterIds: "0150180",
};
const response = await MigrosAPI.products.productStock.getProductSupply(
    productSupplyOptions,
    { leshopch: guestInfo.token },
);



  