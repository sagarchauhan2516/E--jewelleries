async function fetchPrices() {
    const headers = {
      "x-access-token": "goldapi-3ftrvsmccxheq3-io", // Replace with your actual API key
      "Content-Type": "application/json"
    };
  
    try {
      const goldRes = await fetch("https://www.goldapi.io/api/XAU/INR", { headers });
      const silverRes = await fetch("https://www.goldapi.io/api/XAG/INR", { headers });
  
      if (!goldRes.ok || !silverRes.ok) {
        throw new Error("API response not OK");
      }
  
      const goldData = await goldRes.json();
      const silverData = await silverRes.json();
  
      const goldPerGram = (goldData.price / 31.1035).toFixed(2);
      const silverPerGram = (silverData.price / 31.1035).toFixed(2);
  
      const text = `🟡 Gold: ₹${goldPerGram}/g   ⚪ Silver: ₹${silverPerGram}/g   ⏱ Updated: ${new Date().toLocaleTimeString()}`;
      document.getElementById("priceTicker").innerText = text;
    } catch (error) {
      console.error("Error fetching prices:", error);
      document.getElementById("priceTicker").innerText = "⚠ Error fetching gold/silver prices.";
    }
  }
  
  fetchPrices();
  setInterval(fetchPrices, 100000);
  
 