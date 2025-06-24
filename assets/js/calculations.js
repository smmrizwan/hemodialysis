/**
 * Medical Calculations for Dialysis Management System
 */

// Age calculation from date of birth
function calculateAge() {
    const dobInput = document.getElementById('dateOfBirth');
    const ageInput = document.getElementById('calculatedAge');
    
    if (dobInput && ageInput && dobInput.value) {
        const dob = new Date(dobInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        
        ageInput.value = age;
    }
}

// BMI calculation
function calculateBMI() {
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bmiInput = document.getElementById('calculatedBMI');
    
    if (heightInput && weightInput && bmiInput) {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (height > 0 && weight > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(2);
            
            // Also calculate BSA when BMI is calculated
            calculateBSA();
        } else {
            bmiInput.value = '';
            // Clear BSA if height or weight is invalid
            const bsaInput = document.getElementById('calculatedBSA');
            if (bsaInput) bsaInput.value = '';
        }
    }
}

// Body Surface Area (BSA) calculation using Mosteller formula
function calculateBSA() {
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bsaInput = document.getElementById('calculatedBSA');
    
    if (heightInput && weightInput && bsaInput) {
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (height > 0 && weight > 0) {
            // BSA = 0.007184 * Height^0.725 * Weight^0.425
            const bsa = 0.007184 * Math.pow(height, 0.725) * Math.pow(weight, 0.425);
            bsaInput.value = bsa.toFixed(3);
        } else {
            bsaInput.value = '';
        }
    }
}

// Mean Arterial Pressure (MAP) calculation
function calculateMAP(type) {
    const systolicId = type + 'Systolic';
    const diastolicId = type + 'Diastolic';
    const mapId = type + 'MAP';
    
    const systolicInput = document.getElementById(systolicId);
    const diastolicInput = document.getElementById(diastolicId);
    const mapInput = document.getElementById(mapId);
    
    if (systolicInput && diastolicInput && mapInput) {
        const systolic = parseFloat(systolicInput.value);
        const diastolic = parseFloat(diastolicInput.value);
        
        if (systolic > 0 && diastolic > 0) {
            const map = diastolic + ((systolic - diastolic) / 3);
            mapInput.value = map.toFixed(2);
        } else {
            mapInput.value = '';
        }
    }
}

// Dialysis duration calculation
function calculateDialysisDuration() {
    const dialysisDateInput = document.getElementById('dialysisDate');
    const monthsInput = document.getElementById('dialysisMonths');
    const yearsInput = document.getElementById('dialysisYears');
    
    if (dialysisDateInput && monthsInput && yearsInput && dialysisDateInput.value) {
        const startDate = new Date(dialysisDateInput.value);
        const today = new Date();
        
        let months = (today.getFullYear() - startDate.getFullYear()) * 12;
        months -= startDate.getMonth();
        months += today.getMonth();
        
        if (today.getDate() < startDate.getDate()) {
            months--;
        }
        
        const years = Math.floor(months / 12);
        
        monthsInput.value = months;
        yearsInput.value = years;
    }
}

// TSAT calculation: (Iron × 100) / TIBC
function calculateTSAT() {
    const ironInput = document.getElementById('iron');
    const tibcInput = document.getElementById('tibc');
    const tsatInput = document.getElementById('calculatedTSAT');
    
    if (ironInput && tibcInput && tsatInput) {
        const iron = parseFloat(ironInput.value);
        const tibc = parseFloat(tibcInput.value);
        
        if (iron >= 0 && tibc > 0) {
            const tsat = (iron * 100) / tibc;
            tsatInput.value = tsat.toFixed(2);
        } else {
            tsatInput.value = '';
        }
    }
}

// Corrected Calcium calculation: Total Calcium + 0.02 × [40 - Albumin]
function calculateCorrectedCalcium() {
    const calciumInput = document.getElementById('calcium');
    const albuminInput = document.getElementById('albumin');
    const correctedCalciumInput = document.getElementById('correctedCalcium');
    
    if (calciumInput && albuminInput && correctedCalciumInput) {
        const calcium = parseFloat(calciumInput.value);
        const albumin = parseFloat(albuminInput.value);
        
        if (calcium > 0 && albumin > 0) {
            const correctedCalcium = calcium + 0.02 * (40 - albumin);
            correctedCalciumInput.value = correctedCalcium.toFixed(3);
            
            // Trigger Ca×Phos product calculation
            calculateCaPhosProduct();
        } else {
            correctedCalciumInput.value = '';
        }
    }
}

// Calcium × Phosphorus Product calculation
function calculateCaPhosProduct() {
    const correctedCalciumInput = document.getElementById('correctedCalcium');
    const phosphorusInput = document.getElementById('phosphorus');
    const caPhosProductInput = document.getElementById('caPhosProduct');
    
    if (correctedCalciumInput && phosphorusInput && caPhosProductInput) {
        const correctedCalcium = parseFloat(correctedCalciumInput.value);
        const phosphorus = parseFloat(phosphorusInput.value);
        
        if (correctedCalcium > 0 && phosphorus > 0) {
            // Convert mmol/L to mg/dL
            // Calcium mmol/L × 4.008 = mg/dL
            // Phosphorus mmol/L × 3.097 = mg/dL
            const calciumMgDl = correctedCalcium * 4.008;
            const phosphorusMgDl = phosphorus * 3.097;
            
            const product = calciumMgDl * phosphorusMgDl;
            caPhosProductInput.value = product.toFixed(2);
        } else {
            caPhosProductInput.value = '';
        }
    }
}

// URR (Urea Reduction Ratio) calculation: ((Pre BUN - Post BUN) / Pre BUN) × 100
function calculateURR() {
    const preBUNInput = document.getElementById('preBUN');
    const postBUNInput = document.getElementById('postBUN');
    const urrInput = document.getElementById('calculatedURR');
    
    if (preBUNInput && postBUNInput && urrInput) {
        const preBUN = parseFloat(preBUNInput.value);
        const postBUN = parseFloat(postBUNInput.value);
        
        if (preBUN > 0 && postBUN >= 0) {
            const urr = ((preBUN - postBUN) / preBUN) * 100;
            urrInput.value = urr.toFixed(2);
        } else {
            urrInput.value = '';
        }
    }
}

// Kt/V calculation (simplified single-pool model)
function calculateKtV() {
    const preBUNInput = document.getElementById('preBUN');
    const postBUNInput = document.getElementById('postBUN');
    const durationInput = document.getElementById('dialysisDuration');
    const weightInput = document.getElementById('postWeight');
    const ktvInput = document.getElementById('calculatedKtV');
    
    if (preBUNInput && postBUNInput && durationInput && weightInput && ktvInput) {
        const preBUN = parseFloat(preBUNInput.value);
        const postBUN = parseFloat(postBUNInput.value);
        const duration = parseFloat(durationInput.value);
        const weight = parseFloat(weightInput.value);
        
        if (preBUN > 0 && postBUN > 0 && duration > 0 && weight > 0) {
            // Single-pool Kt/V calculation using natural logarithm
            const ratio = postBUN / preBUN;
            if (ratio > 0) {
                const ktv = -Math.log(ratio) + (4 * (preBUN - postBUN)) / (preBUN * 100);
                ktvInput.value = ktv.toFixed(2);
            }
        } else {
            ktvInput.value = '';
        }
    }
}

// Hemoglobin change percentage calculation
function calculateHbChange(currentHb, previousLabData) {
    if (!currentHb || !previousLabData || previousLabData.length === 0) {
        return 0;
    }
    
    // Find the most recent previous Hb value
    let previousHb = null;
    for (let i = 0; i < previousLabData.length; i++) {
        if (previousLabData[i].hb) {
            previousHb = parseFloat(previousLabData[i].hb);
            break;
        }
    }
    
    if (previousHb) {
        return ((currentHb - previousHb) / previousHb) * 100;
    }
    
    return 0;
}

// Quarterly Lab Hemoglobin calculations
function calculateHbChange() {
    const hb1 = parseFloat(document.querySelector('input[name="hb_1"]')?.value || 0); // Hb_last
    const hb2 = parseFloat(document.querySelector('input[name="hb_2"]')?.value || 0); // Hb_current
    
    if (hb1 > 0 && hb2 > 0) {
        // Calculate percentage difference using your formula
        const nchange = Math.abs(hb1 - hb2);
        const pchangeo = hb1 + hb2;
        const pchange = pchangeo / 2;
        const change = nchange / pchange;
        const hbPercentageDifference = change * 100;
        
        const diffInput = document.querySelector('input[name="hb_diff_1_2"]');
        if (diffInput) diffInput.value = hbPercentageDifference.toFixed(2);
        
        // Calculate percentage change using your formula
        const pchangeb = nchange / hb2;
        const hbPercentageChange = pchangeb * 100;
        
        const changeInput = document.querySelector('input[name="hb_change_1_2"]');
        if (changeInput) changeInput.value = hbPercentageChange.toFixed(2);
    }
}

// PTH conversion calculations
function calculatePTHConversion() {
    // Convert PTH from Pmol/L to pg/mL using formula: pg/mL = 9.43 * Pmol/L
    const pthPmol1 = parseFloat(document.querySelector('input[name="pth_pmol_1"]')?.value || 0);
    const pthPmol2 = parseFloat(document.querySelector('input[name="pth_pmol_2"]')?.value || 0);
    const pthPmol3 = parseFloat(document.querySelector('input[name="pth_pmol_3"]')?.value || 0);
    
    if (pthPmol1 > 0) {
        const pthPgml1 = pthPmol1 * 9.43;
        const input1 = document.querySelector('input[name="pth_pgml_1"]');
        if (input1) input1.value = pthPgml1.toFixed(2);
    }
    
    if (pthPmol2 > 0) {
        const pthPgml2 = pthPmol2 * 9.43;
        const input2 = document.querySelector('input[name="pth_pgml_2"]');
        if (input2) input2.value = pthPgml2.toFixed(2);
    }
    
    if (pthPmol3 > 0) {
        const pthPgml3 = pthPmol3 * 9.43;
        const input3 = document.querySelector('input[name="pth_pgml_3"]');
        if (input3) input3.value = pthPgml3.toFixed(2);
    }
}

// Corrected Calcium calculation for quarterly labs
function calculateCorrectedCalcium() {
    // Corrected Calcium = Calcium + 0.02 * (40 - Albumin)
    for (let i = 1; i <= 3; i++) {
        const calcium = parseFloat(document.querySelector(`input[name="calcium_${i}"]`)?.value || 0);
        const albumin = parseFloat(document.querySelector(`input[name="albumin_${i}"]`)?.value || 0);
        
        if (calcium > 0 && albumin > 0) {
            const correctedCalcium = calcium + 0.02 * (40 - albumin);
            const input = document.querySelector(`input[name="corrected_calcium_${i}"]`);
            if (input) input.value = correctedCalcium.toFixed(3);
        }
    }
}

// Ca × Phosphorus product calculation with unit conversions
function calculateCaPhosProduct() {
    for (let i = 1; i <= 3; i++) {
        const calcium = parseFloat(document.querySelector(`input[name="calcium_${i}"]`)?.value || 0);
        const albumin = parseFloat(document.querySelector(`input[name="albumin_${i}"]`)?.value || 0);
        const phosphorusMmol = parseFloat(document.querySelector(`input[name="phosphorus_${i}"]`)?.value || 0);
        
        // Only calculate if ALL three values are present
        if (calcium > 0 && albumin > 0 && phosphorusMmol > 0) {
            // First calculate corrected calcium
            const correctedCalciumMmol = calcium + 0.02 * (40 - albumin);
            
            // Step 1: Convert corrected calcium from mmol/L to mg/dL: mmol/L × 4.008 = mg/dL
            const correctedCalciumMgDl = correctedCalciumMmol * 4.008;
            
            // Step 2: Convert phosphorus from mmol/L to mg/dL: mmol/L × 3.097 = mg/dL
            const phosphorusMgDl = phosphorusMmol * 3.097;
            
            // Step 3: Ca × Phosphorus = Corrected Calcium (mg/dL) × Phosphorus (mg/dL)
            const caPhosProduct = correctedCalciumMgDl * phosphorusMgDl;
            
            const input = document.querySelector(`input[name="ca_phos_product_${i}"]`);
            if (input) input.value = caPhosProduct.toFixed(2);
        } else {
            // Clear the Ca × Phosphorus field if not all values are present
            const input = document.querySelector(`input[name="ca_phos_product_${i}"]`);
            if (input) input.value = '';
        }
    }
}

// Validate numerical input within range
function validateNumberRange(inputId, min, max, errorMsg) {
    const input = document.getElementById(inputId);
    if (input) {
        const value = parseFloat(input.value);
        if (input.value && (isNaN(value) || value < min || value > max)) {
            alert(errorMsg);
            input.focus();
            return false;
        }
    }
    return true;
}

// Format date for display
function formatDate(dateString, format = 'dd-mm-yyyy') {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    switch (format) {
        case 'dd-mm-yyyy':
            return `${day}-${month}-${year}`;
        case 'mm/dd/yyyy':
            return `${month}/${day}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return `${day}-${month}-${year}`;
    }
}

// Calculate duration between two dates
function calculateDuration(startDate, endDate = null) {
    if (!startDate) return { years: 0, months: 0, days: 0 };
    
    const start = new Date(startDate);
    const end = endDate ? new Date(endDate) : new Date();
    
    let years = end.getFullYear() - start.getFullYear();
    let months = end.getMonth() - start.getMonth();
    let days = end.getDate() - start.getDate();
    
    if (days < 0) {
        months--;
        const lastMonth = new Date(end.getFullYear(), end.getMonth(), 0);
        days += lastMonth.getDate();
    }
    
    if (months < 0) {
        years--;
        months += 12;
    }
    
    const totalMonths = years * 12 + months;
    
    return { years, months, days, totalMonths };
}

// Validate required fields in a form
function validateRequiredFields(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// KT/V and URR calculations for quarterly lab data
function calculateKtVandURR(column) {
    const preBunMmol = parseFloat(document.querySelector(`input[name="pre_dialysis_bun_${column}"]`)?.value || 0);
    const postBunMmol = parseFloat(document.querySelector(`input[name="post_dialysis_bun_${column}"]`)?.value || 0);
    const duration = parseFloat(document.querySelector(`input[name="dialysis_duration_${column}"]`)?.value || 0);
    const ufVolume = parseFloat(document.querySelector(`input[name="ultrafiltrate_volume_${column}"]`)?.value || 0);
    const postWeight = parseFloat(document.querySelector(`input[name="post_dialysis_weight_${column}"]`)?.value || 0);
    
    const urrInput = document.querySelector(`input[name="urr_${column}"]`);
    const ktVInput = document.querySelector(`input[name="kt_v_${column}"]`);
    
    if (preBunMmol > 0 && postBunMmol > 0) {
        // Calculate URR: URR = [(Pre dialysis BUN - Post dialysis BUN)/Pre dialysis BUN] × 100%
        const urr = ((preBunMmol - postBunMmol) / preBunMmol) * 100;
        if (urrInput) urrInput.value = urr.toFixed(2);
        
        // Calculate KT/V if all required values are present
        if (duration > 0 && ufVolume >= 0 && postWeight > 0) {
            // Step 1: Convert Pre dialysis BUN from mmol/L to mg/dL (multiply by 2.8011)
            const preBunMgDl = preBunMmol * 2.8011;
            
            // Step 2: Convert Post dialysis BUN from mmol/L to mg/dL (multiply by 2.8011)
            const postBunMgDl = postBunMmol * 2.8011;
            
            // Step 3: Apply KT/V formula
            // Kt/V = -ln((Post BUN/Pre BUN)- 0.03) + (4 - 3.5 × (Post BUN/Pre BUN)) × (UF/Weight))
            const bunRatio = postBunMgDl / preBunMgDl;
            const ufWeightRatio = ufVolume / postWeight;
            
            const ktV = -Math.log(bunRatio - 0.03) + (4 - 3.5 * bunRatio) * ufWeightRatio;
            
            if (ktVInput && !isNaN(ktV) && isFinite(ktV)) {
                ktVInput.value = ktV.toFixed(2);
            }
        } else {
            if (ktVInput) ktVInput.value = '';
        }
    } else {
        if (urrInput) urrInput.value = '';
        if (ktVInput) ktVInput.value = '';
    }
}

// Auto-calculate all dependent fields when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners for automatic calculations
    const calculations = [
        { inputs: ['dateOfBirth'], func: calculateAge },
        { inputs: ['height', 'weight'], func: calculateBMI },
        { inputs: ['preSystolic', 'preDiastolic'], func: () => calculateMAP('pre') },
        { inputs: ['postSystolic', 'postDiastolic'], func: () => calculateMAP('post') },
        { inputs: ['dialysisDate'], func: calculateDialysisDuration },
        { inputs: ['iron', 'tibc'], func: calculateTSAT },
        { inputs: ['calcium', 'albumin'], func: calculateCorrectedCalcium },
        { inputs: ['phosphorus'], func: calculateCaPhosProduct },
        { inputs: ['preBUN', 'postBUN'], func: calculateURR },
        { inputs: ['preBUN', 'postBUN', 'dialysisDuration', 'postWeight'], func: calculateKtV }
    ];
    
    calculations.forEach(calc => {
        calc.inputs.forEach(inputId => {
            const element = document.getElementById(inputId);
            if (element) {
                element.addEventListener('input', calc.func);
                element.addEventListener('change', calc.func);
            }
        });
    });
});

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        calculateAge,
        calculateBMI,
        calculateMAP,
        calculateDialysisDuration,
        calculateTSAT,
        calculateCorrectedCalcium,
        calculateCaPhosProduct,
        calculateURR,
        calculateKtV,
        calculateHbChange,
        validateNumberRange,
        formatDate,
        calculateDuration,
        validateRequiredFields
    };
}
